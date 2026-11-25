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
        'theme',
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
            'is_admin' => 'boolean',
            'approved_at' => 'datetime',
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

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function scopeApproved($q)
    {
        return $q->whereNotNull('approved_at');
    }

    public function scopePending($q)
    {
        return $q->whereNull('approved_at');
    }

    /** Pasangan = akun aktif lain di aplikasi ini. */
    public function partner(): ?User
    {
        return static::approved()->where('id', '!=', $this->id)->orderBy('id')->first();
    }

    /** Target yang bisa dilihat user: miliknya + semua target bersama. */
    public function visibleGoals()
    {
        return Goal::query()->where(fn ($q) => $q->where('user_id', $this->id)->orWhere('is_shared', true));
    }

    /** Tema warna aktif (pink bila belum dipilih / tidak dikenal). */
    public function themeKey(): string
    {
        return array_key_exists($this->theme, config('themes', [])) ? $this->theme : 'pink';
    }

    /** Pendaftaran dibuka selama akun aktif belum penuh (maks. 10 antrian persetujuan). */
    public static function registrationOpen(): bool
    {
        return static::approved()->count() < config('dcfinance.max_users', 2)
            && static::pending()->count() < 10;
    }
}
