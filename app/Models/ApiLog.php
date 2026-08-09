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
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
