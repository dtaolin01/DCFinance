<?php

namespace App\Models;

use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    /** Hasil progress() yang disimpan sementara (bukan kolom database). */
    public ?array $progress_cache = null;

    protected $fillable = [
        'user_id', 'is_shared', 'name', 'emoji', 'color', 'target_amount',
        'target_currency', 'deadline', 'priority', 'notes', 'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
            'target_amount' => 'float',
            'deadline' => 'date',
            'achieved_at' => 'datetime',
            'priority' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Tandai otomatis target yang sudah 100% (dan buka lagi bila turun). */
    public static function syncAchieved(iterable $goals): void
    {
        foreach ($goals as $g) {
            $done = $g->progress_cache['done'] ?? false;
            if ($done && ! $g->achieved_at) {
                $g->achieved_at = now();
                $g->saveQuietly();
            } elseif (! $done && $g->achieved_at) {
                $g->achieved_at = null;
                $g->saveQuietly();
            }
        }
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id || $this->is_shared;
    }

    public function priorityLabel(): string
    {
        return [1 => 'Tinggi', 2 => 'Sedang', 3 => 'Rendah'][$this->priority] ?? 'Sedang';
    }

    /**
     * Hitung semua angka progres target.
     * Uang di mata uang apa pun dinilai ke Rupiah dengan kurs saat ini, lalu ke mata uang target.
     */
    public function progress(?ExchangeRateService $rates = null): array
    {
        $rates ??= app(ExchangeRateService::class);
        $txs = $this->relationLoaded('transactions') ? $this->transactions : $this->transactions()->get();
        $targetRate = $rates->rate($this->target_currency) ?: 1;

        $parts = [];       // per mata uang
        $byUser = [];      // Rupiah per orang
        $savedIdr = 0.0;
        $recentIdr = 0.0;
        $since = Carbon::today()->subDays(90);
        foreach ($txs as $t) {
            $signed = $t->signedAmount();
            $idr = $signed * $rates->rate($t->currency);
            $parts[$t->currency] = ($parts[$t->currency] ?? 0) + $signed;
            $byUser[$t->user_id] = ($byUser[$t->user_id] ?? 0) + $idr;
            $savedIdr += $idr;
            if ($t->date->gte($since)) {
                $recentIdr += $idr;
            }
        }
        $parts = array_filter($parts, fn ($v) => abs($v) >= 0.005);

        $saved = $savedIdr / $targetRate;
        $target = max($this->target_amount, 0.01);
        $remaining = max($target - $saved, 0);
        $percent = min(max($saved / $target * 100, 0), 100);

        // Rencana per periode bila ada tenggat
        $daysLeft = null;
        $perMonth = $perWeek = $perDay = null;
        $overdue = false;
        if ($this->deadline) {
            $daysLeft = (int) Carbon::today()->diffInDays($this->deadline, false);
            $overdue = $daysLeft < 0 && $remaining > 0;
            if ($remaining > 0 && $daysLeft > 0) {
                $perDay = $remaining / $daysLeft;
                $perWeek = $remaining / max($daysLeft / 7, 1);
                $perMonth = $remaining / max($daysLeft / 30.4375, 1);
            }
        }

        // Kecepatan menabung 90 hari terakhir -> perkiraan tanggal tercapai
        $pacePerMonth = ($recentIdr / $targetRate) / (90 / 30.4375);
        $eta = null;
        if ($remaining > 0 && $pacePerMonth > 0) {
            $months = $remaining / $pacePerMonth;
            if ($months < 600) {
                $eta = Carbon::today()->addDays((int) ceil($months * 30.4375));
            }
        }

        $onTrack = null;
        if ($this->deadline && $remaining > 0) {
            $onTrack = $eta !== null && $eta->lte($this->deadline);
        }

        return [
            'saved' => $saved,
            'saved_idr' => $savedIdr,
            'target_idr' => $this->target_amount * $targetRate,
            'parts' => $parts,
            'target' => $this->target_amount,
            'remaining' => $remaining,
            'percent' => $percent,
            'done' => $remaining <= 0,
            'days_left' => $daysLeft,
            'overdue' => $overdue,
            'per_month' => $perMonth,
            'per_week' => $perWeek,
            'per_day' => $perDay,
            'pace_per_month' => $pacePerMonth,
            'eta' => $eta,
            'on_track' => $onTrack,
            'by_user' => $byUser,
            'count' => $txs->count(),
        ];
    }
}
