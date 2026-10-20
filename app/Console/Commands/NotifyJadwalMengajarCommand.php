<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalPelajaran;
use App\Models\Guru;
use App\Models\School;
use App\Models\Setting;
use App\Models\AbsensiGuru;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotifyJadwalMengajarCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absen:notify-jadwal-mengajar 
                            {--minutes=5 : Berapa menit sebelum pelajaran dimulai (default: 5)} 
                            {--day= : Simulasi hari tertentu (misal: Senin, Selasa)} 
                            {--school= : Filter ID sekolah tertentu} 
                            {--guru= : Filter ID atau Nama Guru tertentu} 
                            {--force : Abaikan kalkulasi waktu dan kirimkan langsung untuk jadwal hari ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim push notification (FCM) ke Guru yang mengajar 5 menit sebelum jam pelajaran dimulai';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = (int) ($this->option('minutes') ?: 5);
        $isForce = (bool) $this->option('force');
        $filterSchool = $this->option('school');
        $filterGuru = $this->option('guru');

        $filterDay = $this->option('day');

        $now = Carbon::now();
        $hariMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $namaHari = $filterDay ? ucfirst(strtolower(trim($filterDay))) : ($hariMap[$now->dayOfWeekIso] ?? 'Senin');
        $todayYmd = $now->format('Y-m-d');
        $nowHm = $now->format('H:i');

        $this->info("=== Pengingat Jadwal Mengajar Guru ({$minutes} Menit Sebelum Mulai) ===");
        $this->line("Waktu Server Saat Ini: {$todayYmd} {$now->format('H:i:s')} ({$namaHari})");

        // 1. Ambil semua jadwal untuk hari ini
        $query = JadwalPelajaran::where('hari', $namaHari)
            ->whereNotNull('guru_id')
            ->whereNotNull('jam_mulai')
            ->with(['guru.user', 'kelas', 'mapel', 'school']);

        if ($filterSchool) {
            $query->where('school_id', $filterSchool);
        } else {
            $query->whereHas('school', function ($q) {
                $q->where('is_active', true);
            });
        }

        if ($filterGuru) {
            $query->where(function ($q) use ($filterGuru) {
                $q->where('guru_id', $filterGuru)
                  ->orWhereHas('guru', function ($g) use ($filterGuru) {
                      $g->where('nama', 'like', "%{$filterGuru}%")
                        ->orWhere('nip', $filterGuru);
                  });
            });
        }

        $allJadwals = $query->orderBy('jam_mulai')->get();

        if ($allJadwals->isEmpty()) {
            $this->line("Tidak ada jadwal KBM yang terdaftar untuk hari {$namaHari}.");
            return 0;
        }

        $this->line("Ditemukan {$allJadwals->count()} slot jadwal KBM untuk hari ini. Memeriksa jadwal target...");

        // 2. Kelompokkan jadwal berdasarkan [school_id, guru_id, jam_mulai, mapel_id]
        // Ini bertujuan menyatukan kelas gabungan/paralel agar guru hanya menerima 1 notifikasi terpadu
        $grouped = [];
        foreach ($allJadwals as $j) {
            $cleanJamMulai = substr(trim($j->jam_mulai), 0, 5);
            if (empty($cleanJamMulai) || !str_contains($cleanJamMulai, ':')) {
                continue;
            }

            $key = "{$j->school_id}_{$j->guru_id}_{$cleanJamMulai}_{$j->mapel_id}";
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'school_id'  => $j->school_id,
                    'guru'       => $j->guru,
                    'mapel'      => $j->mapel,
                    'jam_mulai'  => $cleanJamMulai,
                    'jam_selesai'=> substr(trim($j->jam_selesai ?? ''), 0, 5),
                    'ruangan'    => $j->ruangan,
                    'first_id'   => $j->id,
                    'kelases'    => [],
                ];
            }

            if ($j->kelas) {
                $namaK = $j->kelas->nama_kelas ?? $j->kelas->nama ?? ('Kelas ' . $j->kelas->id);
                if (!in_array($namaK, $grouped[$key]['kelases'])) {
                    $grouped[$key]['kelases'][] = $namaK;
                }
            }
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($grouped as $groupKey => $item) {
            $guru = $item['guru'];
            if (!$guru) {
                continue;
            }

            $schoolId = $item['school_id'];
            $jamMulai = $item['jam_mulai'];
            $jamSelesai = $item['jam_selesai'];
            $mapelName = $item['mapel']?->nama_mapel ?? $item['mapel']?->nama ?? 'Mata Pelajaran';
            $kelasNames = !empty($item['kelases']) ? implode(' & ', $item['kelases']) : 'Kelas';
            $ruangan = $item['ruangan'];

            // Cek apakah fitur notifikasi jadwal KBM dimatikan untuk sekolah ini
            $settingEnabled = Setting::getVal('notify_jadwal_kbm_enabled', 'true', $schoolId);
            if ($settingEnabled === 'false' || $settingEnabled === '0') {
                continue;
            }

            // 3. Hitung selisih waktu
            try {
                $startTime = Carbon::createFromFormat('Y-m-d H:i', "{$todayYmd} {$jamMulai}");
            } catch (\Exception $e) {
                continue;
            }

            $diffInSeconds = $now->diffInSeconds($startTime, false); // Positif jika startTime di masa depan
            $diffInMinutes = round($diffInSeconds / 60);

            // Waktu target notifikasi (misal 5 menit sebelum startTime)
            $targetNotifTimeStr = $startTime->copy()->subMinutes($minutes)->format('H:i');

            $isTimeMatch = false;

            if ($isForce) {
                $isTimeMatch = true;
            } else {
                // Cocok jika menit saat ini persis waktu target notifikasi (e.g. 07:10 untuk pelajaran 07:15)
                // ATAU rentang toleransi detik antara ($minutes * 60 - 50) s/d ($minutes * 60 + 50)
                $minSec = ($minutes * 60) - 50;
                $maxSec = ($minutes * 60) + 50;

                if ($nowHm === $targetNotifTimeStr || ($diffInSeconds >= $minSec && $diffInSeconds <= $maxSec)) {
                    $isTimeMatch = true;
                }
            }

            if (!$isTimeMatch) {
                continue;
            }

            // 4. Anti-duplicate: Pastikan untuk sesi hari ini notifikasi hanya dikirim 1 kali
            $cleanJamKey = str_replace(':', '', $jamMulai);
            $cacheKey = "notif_jadwal_mengajar_{$schoolId}_{$guru->id}_{$todayYmd}_{$cleanJamKey}";

            if (!$isForce && Cache::has($cacheKey)) {
                $skippedCount++;
                continue;
            }

            // 5. Cek apakah Guru sedang berhalangan hadir (Izin/Sakit/Cuti) hari ini
            $isAbsentToday = AbsensiGuru::where('guru_id', $guru->id)
                ->where('tanggal', $todayYmd)
                ->whereIn('status', ['I', 'S', 'C', 'izin', 'sakit', 'cuti'])
                ->exists();

            if ($isAbsentToday) {
                $this->line("⏩ Guru {$guru->nama} sedang Izin/Sakit/Cuti hari ini. Pengingat dilewati.");
                continue;
            }

            // 6. Siapkan Teks Notifikasi & Payload
            $title = "⏰ Pengingat Jadwal Mengajar ({$minutes} Menit Lagi)";
            $ruanganText = !empty($ruangan) ? " di {$ruangan}" : "";
            $message = "Halo Bapak/Ibu {$guru->nama}, {$minutes} menit lagi ({$jamMulai} WIB) Anda memiliki jadwal mengajar {$mapelName} di kelas {$kelasNames}{$ruanganText}. Silakan persiapkan materi & presensi siswa.";

            $dataPayload = [
                'type'         => 'reminder_jadwal_mengajar',
                'action'       => 'open_jadwal',
                'schedule_id'  => (string) $item['first_id'],
                'mapel'        => (string) $mapelName,
                'kelas'        => (string) $kelasNames,
                'jam_mulai'    => (string) $jamMulai,
                'jam_selesai'  => (string) $jamSelesai,
                'ruangan'      => (string) ($ruangan ?: '-'),
                'tanggal'      => (string) $todayYmd,
            ];

            // 7. Kirim Push Notification via FCM
            $sent = FcmNotificationService::sendToGuru($guru, $title, $message, $dataPayload);

            if ($sent) {
                Cache::put($cacheKey, true, now()->addDay());
                $sentCount++;
                $this->info("✅ [TERKIRIM] {$guru->nama} - {$mapelName} ({$kelasNames}) jam {$jamMulai} WIB");
                Log::info("Push Notif Jadwal Mengajar Terkirim -> Guru: {$guru->nama}, Mapel: {$mapelName}, Jam: {$jamMulai}");
            } else {
                $errorMsg = FcmNotificationService::$lastError ?? 'Perangkat guru belum memiliki token FCM aktif';
                $this->warn("⚠️  [TIDAK TERKIRIM] {$guru->nama}: {$errorMsg}");
            }
        }

        $this->info("Selesai. Total terkirim: {$sentCount}, Dilewati (sudah terkirim sebelumnya): {$skippedCount}");
        return 0;
    }
}
