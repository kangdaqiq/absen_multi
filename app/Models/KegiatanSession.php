<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KegiatanSession extends Model
{
    protected $table = 'kegiatan_sessions';
    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'kegiatan_id',
        'uid_kartu',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Cek apakah sesi ini masih aktif.
     */
    public function isActive(): bool
    {
        return $this->expires_at >= now();
    }
}
