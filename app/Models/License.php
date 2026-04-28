<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'client_name',
        'max_schools',
        'max_students',
        'expired_at',
        'is_active',
        'allowed_hostname',
        'notes',
        'last_ping_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'expired_at'   => 'date',
        'last_ping_at' => 'datetime',
        'max_schools'  => 'integer',
        'max_students' => 'integer',
    ];

    /**
     * Generate a unique license key (XXXX-XXXX-XXXX-XXXX)
     */
    public static function generateKey(): string
    {
        do {
            $key = strtoupper(implode('-', [
                Str::random(6),
                Str::random(6),
                Str::random(6),
                Str::random(6),
            ]));
        } while (self::where('license_key', $key)->exists());

        return $key;
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) return 'Nonaktif';
        if ($this->isExpired()) return 'Expired';
        return 'Aktif';
    }

    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) return 'secondary';
        if ($this->isExpired()) return 'warning';
        return 'success';
    }
}
