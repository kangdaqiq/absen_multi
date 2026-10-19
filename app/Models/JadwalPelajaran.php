<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    /**
     * Cache static per request agar tidak query JamPelajaran berulang kali
     */
    protected static $cachedSchoolJamPelajarans = [];

    /**
     * Deteksi jeda istirahat yang berada di tengah rentang jam KBM ini.
     * Mengembalikan array jeda istirahat:
     * [
     *   [
     *     'jam_mulai' => '10:00',
     *     'jam_selesai' => '10:15',
     *     'durasi_menit' => 15,
     *     'label' => '10:00 - 10:15',
     *     'setelah_jam_ke' => '4'
     *   ]
     * ]
     *
     * @param \Illuminate\Support\Collection|null $cachedJamPelajarans
     * @return array
     */
    public function getJedaIstirahat($cachedJamPelajarans = null): array
    {
        if (empty($this->jam_mulai) || empty($this->jam_selesai)) {
            return [];
        }

        $mulai = substr($this->jam_mulai, 0, 5);
        $selesai = substr($this->jam_selesai, 0, 5);

        if ($mulai >= $selesai) {
            return [];
        }

        $jps = $cachedJamPelajarans;
        if ($jps === null) {
            $schoolId = $this->school_id ?? 0;
            $hari = $this->hari;
            $cacheKey = "{$schoolId}_{$hari}";

            if (!isset(self::$cachedSchoolJamPelajarans[$cacheKey])) {
                self::$cachedSchoolJamPelajarans[$cacheKey] = JamPelajaran::where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->when($hari, function ($q) use ($hari) {
                        $q->where(function ($sq) use ($hari) {
                            $sq->whereNull('hari')
                                ->orWhere('hari', 'like', "%{$hari}%");
                        });
                    })
                    ->orderBy('urutan')
                    ->orderBy('jam_mulai')
                    ->get();
            }
            $jps = self::$cachedSchoolJamPelajarans[$cacheKey];
        }

        if (!$jps || $jps->isEmpty()) {
            return [];
        }

        // 1. Coba cocokkan periode berdasarkan jam_pelajaran_id dan jam_pelajaran_id_akhir
        $matched = collect();
        $awal = $this->jam_pelajaran_id ? $jps->firstWhere('id', $this->jam_pelajaran_id) : null;
        $akhir = $this->jam_pelajaran_id_akhir ? $jps->firstWhere('id', $this->jam_pelajaran_id_akhir) : $awal;

        if ($awal && $akhir) {
            $minUrutan = min($awal->urutan, $akhir->urutan);
            $maxUrutan = max($awal->urutan, $akhir->urutan);
            $matched = $jps->filter(function ($jp) use ($awal, $minUrutan, $maxUrutan) {
                $isSameSession = empty($awal->nama_jam) || empty($jp->nama_jam) || (strcasecmp(trim($jp->nama_jam), trim($awal->nama_jam)) === 0);
                return $isSameSession && $jp->urutan >= $minUrutan && $jp->urutan <= $maxUrutan;
            })->sortBy('urutan')->values();
        }

        // 2. Fallback: jika tidak ditemukan via ID atau kurang dari 2 periode, cari berdasarkan irisan jam
        if ($matched->count() < 2) {
            $matched = $jps->filter(function ($jp) use ($mulai, $selesai) {
                $jpMulai = substr($jp->jam_mulai, 0, 5);
                $jpSelesai = substr($jp->jam_selesai, 0, 5);
                return $jpMulai >= $mulai && $jpSelesai <= $selesai;
            })->sortBy('jam_mulai')->values();
        }

        if ($matched->count() < 2) {
            return [];
        }

        $breaks = [];
        $prev = null;

        foreach ($matched as $curr) {
            if ($prev !== null) {
                $prevEnd = substr($prev->jam_selesai, 0, 5);
                $currStart = substr($curr->jam_mulai, 0, 5);

                // Cek jeda waktu kosong di antara 2 jam pelajaran berurutan
                if ($currStart > $prevEnd) {
                    try {
                        $pEndCarbon = Carbon::parse($prevEnd);
                        $cStartCarbon = Carbon::parse($currStart);
                        $durasi = abs($cStartCarbon->diffInMinutes($pEndCarbon));

                        if ($durasi >= 5) {
                            $breaks[] = [
                                'jam_mulai' => $prevEnd,
                                'jam_selesai' => $currStart,
                                'durasi_menit' => $durasi,
                                'label' => "{$prevEnd} - {$currStart}",
                                'setelah_jam_ke' => $prev->jam_ke ?? null,
                            ];
                        }
                    } catch (\Throwable $e) {
                        // ignore parsing error
                    }
                }
            }
            $prev = $curr;
        }

        return $breaks;
    }

    /**
     * Dapatkan detail jeda istirahat terformat (Aksesori)
     */
    public function getJedaIstirahatInfoAttribute(): array
    {
        $breaks = $this->getJedaIstirahat();
        $hasBreak = !empty($breaks);

        $labels = array_map(fn($b) => "{$b['jam_mulai']} - {$b['jam_selesai']}", $breaks);
        $labelsText = implode(', ', $labels);
        $totalDurasi = array_sum(array_column($breaks, 'durasi_menit'));

        $segments = [];
        $mulai = substr($this->jam_mulai, 0, 5);
        $selesai = substr($this->jam_selesai, 0, 5);

        if ($hasBreak) {
            $currStart = $mulai;
            foreach ($breaks as $b) {
                if ($b['jam_mulai'] > $currStart) {
                    $segments[] = "{$currStart} - {$b['jam_mulai']}";
                }
                $currStart = $b['jam_selesai'];
            }
            if ($currStart < $selesai) {
                $segments[] = "{$currStart} - {$selesai}";
            }
        } else {
            $segments[] = "{$mulai} - {$selesai}";
        }

        return [
            'has_istirahat'          => $hasBreak,
            'istirahat_label'        => $hasBreak ? "Istirahat: {$labelsText}" : null,
            'istirahat_text'         => $labelsText ?: null,
            'durasi_istirahat_menit' => $totalDurasi,
            'segments'               => $segments,
            'segments_format'        => implode(' & ', $segments),
            'list'                   => $breaks,
        ];
    }
}
