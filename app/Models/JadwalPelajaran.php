<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    use HasFactory;

    protected $table = 'jadwal_pelajaran';

    protected $fillable = [
        'school_id',
        'jam_pelajaran_id',
        'jam_pelajaran_id_akhir',
        'guru_id',
        'kelas_id',
        'mapel_id',
        'hari',
        'jam_ke',
        'jam_mulai',
        'jam_selesai',
        'ruangan',
        'keterangan',
    ];

    public function jamPelajaran()
    {
        return $this->belongsTo(JamPelajaran::class, 'jam_pelajaran_id');
    }

    public function jamPelajaranAkhir()
    {
        return $this->belongsTo(JamPelajaran::class, 'jam_pelajaran_id_akhir');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    /**
     * Absensi Guru pada sesi jadwal pelajaran ini (jika ada)
     */
    public function absensis()
    {
        return $this->hasMany(AbsensiGuru::class, 'jadwal_pelajaran_id');
    }

    /**
     * Jurnal KBM / materi pembelajaran
     */
    public function jurnals()
    {
        return $this->hasMany(JurnalPelajaran::class, 'jadwal_pelajaran_id');
    }

    /**
     * Absensi siswa per jam pelajaran
     */
    public function absensiSiswa()
    {
        return $this->hasMany(AbsensiPelajaran::class, 'jadwal_pelajaran_id');
    }

    /**
     * Format jam tampilan (07:15 - 08:35)
     */
    public function getJamFormatAttribute(): string
    {
        $mulai = $this->jam_mulai ? substr($this->jam_mulai, 0, 5) : '';
        $selesai = $this->jam_selesai ? substr($this->jam_selesai, 0, 5) : '';
        return "{$mulai} - {$selesai}";
    }

    /**
     * Konversi hari ke index hari (1 = Senin ... 7 = Minggu)
     */
    public static function hariToIndex(string $hari): int
    {
        $map = [
            'Senin' => 1,
            'Selasa' => 2,
            'Rabu' => 3,
            'Kamis' => 4,
            'Jumat' => 5,
            'Sabtu' => 6,
            'Minggu' => 7,
        ];
        return $map[ucfirst(strtolower(trim($hari)))] ?? 1;
    }

    public static function indexToHari(int $index): string
    {
        $map = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        return $map[$index] ?? 'Senin';
    }
}
