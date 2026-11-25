<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAW = 'withdraw';
    public const EXCHANGE_OUT = 'exchange_out';
    public const EXCHANGE_IN = 'exchange_in';

    public const INFLOWS = [self::DEPOSIT, self::EXCHANGE_IN];
    public const OUTFLOWS = [self::WITHDRAW, self::EXCHANGE_OUT];

    protected $fillable = [
        'user_id', 'goal_id', 'type', 'currency', 'amount', 'rate',
        'amount_idr', 'date', 'method', 'note', 'exchange_group',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'float',
            'rate' => 'float',
            'amount_idr' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function scopeSavings(Builder $q): Builder
    {
        return $q->whereIn('type', [self::DEPOSIT, self::WITHDRAW]);
    }

    public function isInflow(): bool
    {
        return in_array($this->type, self::INFLOWS, true);
    }

    public function isExchange(): bool
    {
        return in_array($this->type, [self::EXCHANGE_IN, self::EXCHANGE_OUT], true);
    }

    /** +amount untuk uang masuk, -amount untuk uang keluar. */
    public function signedAmount(): float
    {
        return $this->isInflow() ? $this->amount : -$this->amount;
    }

    public function signedIdr(): float
    {
        return $this->isInflow() ? $this->amount_idr : -$this->amount_idr;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::DEPOSIT => 'Setor',
            self::WITHDRAW => 'Tarik',
            self::EXCHANGE_OUT => 'Tukar (keluar)',
            self::EXCHANGE_IN => 'Tukar (masuk)',
            default => ucfirst($this->type),
        };
    }

    public function icon(): string
    {
        if ($this->isExchange()) {
            return 'swap';
        }

        return config("dcfinance.methods.{$this->method}.icon", 'coins');
    }

    public function methodLabel(): string
    {
        return config("dcfinance.methods.{$this->method}.label", 'Lainnya');
    }
}
