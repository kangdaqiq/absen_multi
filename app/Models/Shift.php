<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shifts';

    protected $fillable = [
        'school_id',
        'nama_shift',
        'kode_shift',
        'jam_masuk',
        'jam_pulang',
        'jam_terlambat',
        'awal_absen_masuk',
        'akhir_absen_masuk',
        'awal_absen_pulang',
        'akhir_absen_pulang',
        'hari_kerja',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hari_kerja' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function gurus()
    {
        return $this->hasMany(Guru::class, 'default_shift_id');
    }

    public function assignedGurus()
    {
        return $this->belongsToMany(Guru::class, 'guru_shift_assignments', 'shift_id', 'guru_id')->distinct();
    }

    public function shiftAssignments()
    {
        return $this->hasMany(GuruShiftAssignment::class, 'shift_id');
    }

    public function attendances()
    {
        return $this->hasMany(AbsensiGuru::class, 'shift_id');
    }

    /**
     * Cek apakah waktu tap masuk tergolong terlambat
     */
    public function isLate(string $timeStr): bool
    {
        $targetLimit = $this->jam_terlambat ?: $this->jam_masuk;
        if (!$targetLimit) {
            return false;
        }

        $scanTime = Carbon::parse($timeStr)->format('H:i:s');
        $limitTime = Carbon::parse($targetLimit)->format('H:i:s');

        return $scanTime > $limitTime;
    }

    /**
     * Hitung durasi keterlambatan dalam menit (dihitung dari jam_masuk resmi)
     */
    public function calculateLateMinutes(string $timeStr): int
    {
        if (!$this->isLate($timeStr)) {
            return 0;
        }

        $baseTime = Carbon::parse($this->jam_masuk);
        $scanTime = Carbon::parse($timeStr);

        if ($scanTime->gt($baseTime)) {
            return $baseTime->diffInMinutes($scanTime);
        }

        return 0;
    }

    /**
     * Cek apakah waktu tap berada di dalam rentang waktu (window) scan masuk
     */
    public function isInCheckInWindow(string $timeStr): bool
    {
        if (!$this->awal_absen_masuk || !$this->akhir_absen_masuk) {
            return true;
        }

        $scan = Carbon::parse($timeStr)->format('H:i:s');
        $start = Carbon::parse($this->awal_absen_masuk)->format('H:i:s');
        $end = Carbon::parse($this->akhir_absen_masuk)->format('H:i:s');

        return $scan >= $start && $scan <= $end;
    }

    /**
     * Cek apakah waktu tap berada di dalam rentang waktu (window) scan pulang
     */
    public function isInCheckOutWindow(string $timeStr): bool
    {
        if (!$this->awal_absen_pulang || !$this->akhir_absen_pulang) {
            return true;
        }

        $scan = Carbon::parse($timeStr)->format('H:i:s');
        $start = Carbon::parse($this->awal_absen_pulang)->format('H:i:s');
        $end = Carbon::parse($this->akhir_absen_pulang)->format('H:i:s');

        return $scan >= $start && $scan <= $end;
    }

    public function getFormattedJamMasukAttribute(): string
    {
        return $this->jam_masuk ? Carbon::parse($this->jam_masuk)->format('H:i') : '-';
    }

    public function getFormattedJamPulangAttribute(): string
    {
        return $this->jam_pulang ? Carbon::parse($this->jam_pulang)->format('H:i') : '-';
    }

    public function getFormattedJamTerlambatAttribute(): string
    {
        return $this->jam_terlambat ? Carbon::parse($this->jam_terlambat)->format('H:i') : '-';
    }

    /**
     * Cek apakah shift berlaku pada index hari tertentu (1=Senin s/d 7=Minggu)
     */
    public function isActiveOnDay(int $dayIndex): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $days = $this->hari_kerja;
        if (empty($days) || !is_array($days)) {
            // Default Senin s/d Jumat jika belum diatur
            return in_array($dayIndex, [1, 2, 3, 4, 5]);
        }

        return in_array($dayIndex, array_map('intval', $days));
    }

    /**
     * Format hari kerja shift menjadi teks yang mudah dibaca (misal: "Senin - Jumat")
     */
    public function getFormattedHariKerjaAttribute(): string
    {
        $days = $this->hari_kerja;
        if (empty($days) || !is_array($days)) {
            return 'Senin - Jumat';
        }

        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $sortedDays = array_map('intval', $days);
        sort($sortedDays);

        if ($sortedDays === [1, 2, 3, 4, 5]) {
            return 'Senin - Jumat';
        }
        if ($sortedDays === [1, 2, 3, 4, 5, 6]) {
            return 'Senin - Sabtu';
        }
        if ($sortedDays === [1, 2, 3, 4, 5, 6, 7]) {
            return 'Setiap Hari (Senin - Minggu)';
        }

        $names = array_map(fn($d) => $dayNames[$d] ?? $d, $sortedDays);
        return implode(', ', $names);
    }
}
