<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiGuru extends Model
{
    use HasFactory;

    protected $table = 'absensi_guru';

    protected $fillable = [
        'guru_id',
        'jadwal_pelajaran_id', // Nullable for daily
        'shift_id',
        'school_id',
        'tanggal',
        'waktu_hadir', // Keep for backward compatibility or use as created_at
        'jam_masuk',
        'jam_pulang',
        'menit_terlambat',
        'status',
        'status_kehadiran',
        'keterangan'
    ];

    protected static function booted()
    {
        static::creating(function ($absensi) {
            if (empty($absensi->waktu_hadir)) {
                if (!empty($absensi->tanggal) && !empty($absensi->jam_masuk)) {
                    try {
                        $absensi->waktu_hadir = \Carbon\Carbon::parse($absensi->tanggal . ' ' . $absensi->jam_masuk);
                    } catch (\Exception $e) {
                        $absensi->waktu_hadir = now();
                    }
                } elseif (!empty($absensi->tanggal)) {
                    try {
                        $absensi->waktu_hadir = \Carbon\Carbon::parse($absensi->tanggal . ' ' . now()->format('H:i:s'));
                    } catch (\Exception $e) {
                        $absensi->waktu_hadir = now();
                    }
                } else {
                    $absensi->waktu_hadir = now();
                }
            }
        });
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
