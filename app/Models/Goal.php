<?php

namespace App\Models;

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
     * AUD yang terkumpul dinilai dengan kurs saat ini (nilai pasar sekarang).
     */
    public function progress(float $audRate): array
    {
        $txs = $this->relationLoaded('transactions') ? $this->transactions : $this->transactions()->get();

        $idrPart = 0.0;
        $audPart = 0.0;
        $byUser = [];
        foreach ($txs as $t) {
            $signed = $t->signedAmount();
            if ($t->currency === 'AUD') {
                $audPart += $signed;
            } else {
                $idrPart += $signed;
            }
            $byUser[$t->user_id] = ($byUser[$t->user_id] ?? 0) + ($t->currency === 'AUD' ? $signed * $audRate : $signed);
        }

        $savedIdr = $idrPart + $audPart * $audRate;
        $saved = $this->target_currency === 'AUD'
            ? $audPart + ($audRate > 0 ? $idrPart / $audRate : 0)
            : $savedIdr;

        $target = max($this->target_amount, 0.01);
        $remaining = max($target - $saved, 0);
        $percent = min(max($saved / $target * 100, 0), 100);

        // Rencana per periode bila ada deadline
        $daysLeft = null;
        $perMonth = $perWeek = $perDay = null;
        $overdue = false;
        if ($this->deadline) {
            $today = Carbon::today();
            $daysLeft = (int) $today->diffInDays($this->deadline, false);
            $overdue = $daysLeft < 0 && $remaining > 0;
            if ($remaining > 0 && $daysLeft > 0) {
                $perDay = $remaining / $daysLeft;
                $perWeek = $remaining / max($daysLeft / 7, 1);
                $perMonth = $remaining / max($daysLeft / 30.4375, 1);
            }
        }

        // Kecepatan menabung 90 hari terakhir -> perkiraan tanggal tercapai
        $since = Carbon::today()->subDays(90);
        $recent = 0.0;
        foreach ($txs as $t) {
            if ($t->date->gte($since)) {
                $v = $t->currency === 'AUD' ? $t->signedAmount() * $audRate : $t->signedAmount();
                $recent += $v;
            }
        }
        if ($this->target_currency === 'AUD' && $audRate > 0) {
            $recent /= $audRate;
        }
        $pacePerMonth = $recent / (90 / 30.4375);
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
            'idr_part' => $idrPart,
            'aud_part' => $audPart,
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
