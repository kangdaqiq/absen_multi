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
        'hari',
        'jam_mulai',
        'jam_selesai',
        'uid_kartu',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'tanggal_mulai'   => 'date',
        'hari'            => 'array',
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
        } elseif ($this->frekuensi === 'harian') {
            if (!empty($this->hari) && is_array($this->hari) && count($this->hari) < 7) {
                $dayIndex = $now->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
                $hariList = array_map('intval', $this->hari);
                if (!in_array($dayIndex, $hariList)) {
                    return false;
                }
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

    /**
     * Accessor untuk teks frekuensi & hari kegiatan.
     */
    public function getFormattedHariAttribute(): string
    {
        if ($this->frekuensi !== 'harian') {
            return match($this->frekuensi) {
                'sekali' => 'Sekali (Insidental)',
                'mingguan' => 'Mingguan',
                'bulanan' => 'Bulanan',
                default => ucfirst($this->frekuensi ?? 'Harian')
            };
        }

        $hari = $this->hari;
        if (empty($hari) || !is_array($hari) || count($hari) === 7) {
            return 'Harian (Setiap Hari)';
        }

        $hariInt = array_map('intval', $hari);
        sort($hariInt);

        if ($hariInt === [1, 2, 3, 4, 5]) {
            return 'Harian (Senin - Jumat)';
        }
        if ($hariInt === [1, 2, 3, 4, 5, 6]) {
            return 'Harian (Senin - Sabtu)';
        }

        $map = [
            1 => 'Sen',
            2 => 'Sel',
            3 => 'Rab',
            4 => 'Kam',
            5 => 'Jum',
            6 => 'Sab',
            7 => 'Min',
        ];

        $names = array_map(fn($d) => $map[$d] ?? '', $hariInt);
        return 'Harian (' . implode(', ', array_filter($names)) . ')';
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
