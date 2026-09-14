<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $table = 'api_logs';
    public $timestamps = false; // Manually managed in legacy logic
    protected $fillable = ['school_id', 'api_key', 'action', 'uid', 'success', 'message', 'ip_address', 'user_agent', 'created_at'];

    protected static function booted()
    {
        static::creating(function ($apiLog) {
            if (empty($apiLog->api_key)) {
                $apiLog->api_key = 'SYSTEM';
            }

            // Tambahkan keterangan "[Sync]" jika request berasal dari sinkronisasi offline (membawa scanned_at)
            if (function_exists('request') && request() && request()->filled('scanned_at') && !str_contains($apiLog->message ?? '', '[Sync]')) {
                $apiLog->message = '[Sync] ' . ($apiLog->message ?? '');
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
