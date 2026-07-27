<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    protected $table = 'kegiatans';
    public $timestamps = true;

    protected $fillable = [
        'school_id',
        'nama_kegiatan',
        'deskripsi',
        'tanggal_mulai',
        'frekuensi',
        'jam_mulai',
        'jam_selesai',
        'uid_kartu',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'tanggal_mulai'   => 'date',
    ];

    /**
     * Cek apakah kegiatan sedang berjalan berdasarkan jadwal waktu hari ini.
     */
    public function isScheduledNow($now = null): bool
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return false;
        }
        $now = $now ? \Carbon\Carbon::parse($now) : now();
        $nowTimeStr = $now->format('H:i:s');
        $todayStr = $now->format('Y-m-d');
        
        $startDateStr = $this->tanggal_mulai->format('Y-m-d');
        if ($todayStr < $startDateStr) {
            return false;
        }

        // Check frekuensi
        if ($this->frekuensi === 'sekali') {
            if ($todayStr !== $startDateStr) {
                return false;
            }
        } elseif ($this->frekuensi === 'mingguan') {
            if ($now->dayOfWeek !== $this->tanggal_mulai->dayOfWeek) {
                return false;
            }
        } elseif ($this->frekuensi === 'bulanan') {
            if ($now->day !== $this->tanggal_mulai->day) {
                return false;
            }
        }

        return $nowTimeStr >= $this->jam_mulai && $nowTimeStr <= $this->jam_selesai;
    }


    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function attendances()
    {
        return $this->hasMany(KegiatanAttendance::class, 'kegiatan_id');
    }

    public function sessions()
    {
        return $this->hasMany(KegiatanSession::class, 'kegiatan_id');
    }

    public function activeSession()
    {
        return $this->hasOne(KegiatanSession::class, 'kegiatan_id')
            ->where('expires_at', '>=', now());
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
