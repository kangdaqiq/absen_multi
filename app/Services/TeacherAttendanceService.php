<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\AbsensiGuru;
use Carbon\Carbon;

class TeacherAttendanceService
{
    /**
     * Check schedule and record attendance if applicable.
     * Returns array with details for the response.
     */
    public function handleAttendance(Guru $guru)
    {
        $now = Carbon::now();
        $hari = $this->getHari($now->dayOfWeek); // 0 (Sun) - 6 (Sat)
        
        // Find active schedule
        // Logic: jam_mulai <= NOW <= jam_selesai
        // We might want to allow checking in a bit early? Say 15 mins before?
        // For now, strict or slightly loose. Let's say 15 mins before start is OK.
        
        $jadwal = JadwalPelajaran::where('guru_id', $guru->id)
            ->where('hari', $hari)
            ->where('jam_mulai', '<=', $now->format('H:i:s'))
            ->where('jam_selesai', '>=', $now->format('H:i:s'))
            ->with(['mapel', 'kelas'])
            ->first();
            
        if (!$jadwal) {
            return [
                'recorded' => false,
                'message' => 'No Schedule Found',
                'mapel' => null,
                'kelas' => null
            ];
        }

        // Check if already recorded today
        $attendance = AbsensiGuru::where('guru_id', $guru->id)
            ->where('jadwal_pelajaran_id', $jadwal->id)
            ->where('tanggal', $now->format('Y-m-d'))
            ->first();
            
        if ($attendance) {
             return [
                'recorded' => false, // Already recorded, technically "success" but not a new record
                'status' => 'already_present',
                'message' => 'Sudah Absen: ' . $jadwal->mapel->nama_mapel,
                'mapel' => $jadwal->mapel->nama_mapel,
                'kelas' => $jadwal->kelas->nama_kelas
            ];
        }

        // Record it
        AbsensiGuru::create([
            'guru_id' => $guru->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'tanggal' => $now->format('Y-m-d'),
            'waktu_hadir' => $now,
            'status' => 'Hadir'
        ]);

        return [
            'recorded' => true,
            'message' => 'Hadir: ' . $jadwal->mapel->nama_mapel,
            'mapel' => $jadwal->mapel->nama_mapel,
            'kelas' => $jadwal->kelas->nama_kelas
        ];
    }

    private function getHari($dayIndex)
    {
        // Carbon: 0=Sun, 6=Sat
        // We want: Senin, Selasa...
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];
        return $days[$dayIndex] ?? 'Senin';
    }
}
