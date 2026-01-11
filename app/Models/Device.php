<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'api_keys';

    // Legacy code inserts created_at=NOW(), so timestamps likely enabled.
    // However, does it have updated_at?
    // UPDATE query: UPDATE api_keys SET ... WHERE id = :id. No updated_at mentioned.
    // So updated_at might be missing.
    // I will disable timestamps and manually handle created_at if needed, or just let DB default handle it (if current_timestamp).
    // The INSERT in legacy is: VALUES (:name,:token,:act,NOW())
    // So created_at is managed manually.
    // I'll set public $timestamps = false; and fill created_at in creating event or just leave it if DB has default.
    // Safest matches legacy: timestamps = false;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'api_key',
        'type',
        'active',
        'created_at',
        'school_id'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }
}
