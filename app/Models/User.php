<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Ringkasan tabungan sementara (bukan kolom database). */
    public ?array $summary = null;

    protected $fillable = [
        'name',
        'nickname',
        'color',
        'emoji',
        'monthly_target',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'monthly_target' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /** Nama panggilan (fallback ke kata pertama nama). */
    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?: strtok($this->name, ' ');
    }

    /** Pasangan = akun lain di aplikasi ini. */
    public function partner(): ?User
    {
        return static::where('id', '!=', $this->id)->orderBy('id')->first();
    }

    /** Target yang bisa dilihat user: miliknya + semua target bersama. */
    public function visibleGoals()
    {
        return Goal::query()->where(fn ($q) => $q->where('user_id', $this->id)->orWhere('is_shared', true));
    }

    public static function registrationOpen(): bool
    {
        return static::count() < config('dcfinance.max_users', 2);
    }
}
