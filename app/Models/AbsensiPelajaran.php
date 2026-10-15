<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiPelajaran extends Model
{
    use HasFactory;

    protected $table = 'absensi_pelajaran';

    protected $fillable = [
        'school_id',
        'jadwal_pelajaran_id',
        'jurnal_pelajaran_id',
        'student_id',
        'guru_id',
        'tanggal',
        'status', // H=Hadir, S=Sakit, I=Izin, A=Alpa, T=Terlambat, D=Dispen
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }

    public function jurnal()
    {
        return $this->belongsTo(JurnalPelajaran::class, 'jurnal_pelajaran_id');
    }

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Label readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'H' => 'Hadir',
            'S' => 'Sakit',
            'I' => 'Izin',
            'A' => 'Alpa',
            'T' => 'Terlambat',
            'D' => 'Dispen',
            default => $this->status ?? 'Belum Absen',
        };
    }

    /**
     * Badge CSS class for status
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'H' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400 border border-success-200 dark:border-success-500/20',
            'S' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border border-warning-200 dark:border-warning-500/20',
            'I' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20',
            'A' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400 border border-error-200 dark:border-error-500/20',
            'T' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20',
            'D' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }
}
