<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiSholat extends Model
{
    use HasFactory;

    protected $table = 'absensi_sholat';

    protected $fillable = [
        'school_id',
        'device_id',
        'person_type',
        'student_id',
        'guru_id',
        'tanggal',
        'sholat',
        'jam_absen',
        'waktu_sholat',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function getPersonNameAttribute(): string
    {
        if ($this->person_type === 'guru') {
            return $this->guru?->nama ?? 'Guru Tidak Dikenal';
        }
        return $this->student?->nama ?? 'Siswa Tidak Dikenal';
    }

    public function getSholatLabelAttribute(): string
    {
        return match (strtolower($this->sholat)) {
            'subuh'   => 'Subuh',
            'dhuha'   => 'Dhuha',
            'dzuhur'  => 'Dzuhur',
            'jumat'   => 'Jumat',
            'ashar'   => 'Ashar',
            'maghrib' => 'Maghrib',
            'isya'    => 'Isya',
            default   => ucfirst($this->sholat),
        };
    }
}

