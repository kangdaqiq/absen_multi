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
        'finger_id_min',
        'finger_id_max',
        'active',
        'created_at',
        'school_id'
    ];

    protected $casts = [
        'active' => 'boolean',
        'finger_id_min' => 'integer',
        'finger_id_max' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    /**
     * Antrekan ID sidik jari untuk dihapus dari fisik sensor perangkat di sekolah terkait
     */
    public static function queueFingerDeletion($schoolId, array $fingerIds)
    {
        $fingerIds = array_values(array_unique(array_filter(array_map('intval', $fingerIds))));
        if (empty($fingerIds) || !$schoolId) {
            return;
        }

        $devices = static::where('school_id', $schoolId)->get();
        foreach ($devices as $device) {
            // 1. Antrekan di cache untuk di-poll oleh perangkat
            $queueKey = 'delete_finger_queue_' . $device->id;
            $existingQueue = \Illuminate\Support\Facades\Cache::get($queueKey, []);
            if (!is_array($existingQueue)) {
                $existingQueue = [];
            }
            $mergedQueue = array_values(array_unique(array_merge($existingQueue, $fingerIds)));
            \Illuminate\Support\Facades\Cache::put($queueKey, $mergedQueue, now()->addMinutes(30));

            // Simpan juga single key legacy
            \Illuminate\Support\Facades\Cache::put('delete_finger_' . $device->id, $mergedQueue[0], now()->addMinutes(30));

            // 2. HTTP Push langsung jika IP perangkat tercatat di log terakhir
            $lastLog = \App\Models\ApiLog::where('api_key', $device->api_key)
                ->whereNotNull('ip_address')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastLog && $lastLog->ip_address) {
                foreach ($fingerIds as $fId) {
                    try {
                        \Illuminate\Support\Facades\Http::timeout(2)->get("http://{$lastLog->ip_address}/delete-finger?id=" . $fId);
                    } catch (\Throwable $e) {
                        // Abaikan jika perangkat sedang offline / unreachable
                    }
                }
            }
        }
    }
}
