<?php

namespace App\Services;

use App\Jobs\SendTelegramMessageJob;
use App\Models\School;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendEnrollSuccess($name, $chatId, $uid, $schoolId, $type = 'Kartu RFID', $chatIdOrtu = null)
    {
        if (!$chatId && !$chatIdOrtu) {
            return;
        }

        $school = School::find($schoolId);
        if (!$school || !$school->telegram_enabled || !$school->telegram_bot_token) {
            return;
        }
        $token = $school->telegram_bot_token;

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_ortu')->value('setting_value') !== 'false') : true;

        // Send to student
        if ($chatId && $siswaEnabled) {
            $msg = "✨ <b>PENDAFTARAN BERHASIL</b> ✨\n\n" .
                "Halo, <b>{$name}</b> 👋,\n\n" .
                "Kartu/Perangkat <b>{$type}</b> Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n" .
                "🆔 ID Kartu: <code>{$uid}</code>\n" .
                "📅 Tanggal: " . now()->translatedFormat('l, d F Y') . "\n\n" .
                "<i>Terima kasih telah melakukan registrasi.</i> 🙏";
            
            $this->dispatchJob($token, $chatId, $msg, $schoolId);
        }

        // Send to parent
        if ($chatIdOrtu && $ortuEnabled) {
            $msgOrtu = "✨ <b>PENDAFTARAN BERHASIL</b> ✨\n\n" .
                "Halo, Anak Anda, <b>{$name}</b> 👋,\n\n" .
                "Kartu/Perangkat <b>{$type}</b> telah berhasil didaftarkan ke sistem absensi sekolah.\n\n" .
                "🆔 ID Kartu: <code>{$uid}</code>\n" .
                "📅 Tanggal: " . now()->translatedFormat('l, d F Y') . "\n\n" .
                "<i>Terima kasih telah melakukan registrasi.</i> 🙏";
            
            $this->dispatchJob($token, $chatIdOrtu, $msgOrtu, $schoolId);
        }
    }

    public function sendCheckIn($name, $chatId, $time, $status, $schoolId, $keterangan = null, $chatIdOrtu = null, $kelas = '-')
    {
        if (!$chatId && !$chatIdOrtu) {
            return;
        }

        $school = School::find($schoolId);
        if (!$school || !$school->telegram_enabled || !$school->telegram_bot_token) {
            return;
        }
        $token = $school->telegram_bot_token;

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_ortu')->value('setting_value') !== 'false') : true;

        $statusMap = [
            'H' => 'Hadir',
            'A' => 'Alpha',
            'I' => 'Izin',
            'S' => 'Sakit',
            'B' => 'Bolos',
            'T' => 'Terlambat'
        ];
        $readableStatus = $statusMap[strtoupper($status)] ?? $status;
        $isLate = !empty($keterangan);

        // Student message
        if ($chatId && $siswaEnabled) {
            if ($isLate) {
                [$lateHours, $lateMinutes] = $this->parseLateDuration($keterangan);
                $durationText = $lateHours > 0 ? "{$lateHours} jam {$lateMinutes} menit" : "{$lateMinutes} menit";
                
                $msg = "⚠️ <b>ANDA TERLAMBAT</b> ⚠️\n\n" .
                    "Halo, <b>{$name}</b> 👋,\n" .
                    "Kelas: {$kelas}\n\n" .
                    "Anda terdeteksi melakukan check-in pada pukul <b>{$time}</b>.\n" .
                    "Status: Terlambat selama <b>{$durationText}</b>.\n\n" .
                    "<i>Tetap semangat belajar dan harap perhatikan waktu kehadiran.</i> 👍";
            } else {
                $msg = "✅ <b>ABSEN MASUK BERHASIL</b> ✅\n\n" .
                    "Halo, <b>{$name}</b> 👋,\n" .
                    "Kelas: {$kelas}\n\n" .
                    "Anda telah berhasil absen masuk pada pukul <b>{$time}</b>.\n" .
                    "Status: <b>{$readableStatus}</b>.\n\n" .
                    "<i>Selamat belajar!</i> 📚";
            }
            $this->dispatchJob($token, $chatId, $msg, $schoolId);
        }

        // Parent message
        if ($chatIdOrtu && $ortuEnabled) {
            if ($isLate) {
                [$lateHours, $lateMinutes] = $this->parseLateDuration($keterangan);
                $durationText = $lateHours > 0 ? "{$lateHours} jam {$lateMinutes} menit" : "{$lateMinutes} menit";

                $msgOrtu = "⚠️ <b>Pemberitahuan Keterlambatan</b> ⚠️\n\n" .
                    "Bapak/Ibu Orang Tua/Wali dari <b>{$name}</b> (Kelas: {$kelas}),\n\n" .
                    "Menginfokan bahwa putra/putri Anda telah melakukan check-in sekolah pada pukul <b>{$time}</b> dengan status <b>Terlambat selama {$durationText}</b>.";
            } else {
                $msgOrtu = "🔔 <b>Laporan Absensi Masuk</b> 🔔\n\n" .
                    "Bapak/Ibu Orang Tua/Wali dari <b>{$name}</b> (Kelas: {$kelas}),\n\n" .
                    "Menginfokan bahwa putra/putri Anda telah tiba di sekolah dan melakukan absen masuk pada pukul <b>{$time}</b>.\n" .
                    "Status: <b>{$readableStatus}</b>.";
            }
            $this->dispatchJob($token, $chatIdOrtu, $msgOrtu, $schoolId);
        }
    }

    public function sendCheckOut($name, $chatId, $time, $hours, $mins, $authorizer, $schoolId, $jamMasuk = '-', $chatIdOrtu = null, $tanggal = null)
    {
        if (!$chatId && !$chatIdOrtu) {
            return;
        }

        $school = School::find($schoolId);
        if (!$school || !$school->telegram_enabled || !$school->telegram_bot_token) {
            return;
        }
        $token = $school->telegram_bot_token;
        $dateText = $tanggal ?: now()->format('d/m/Y');

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_ortu')->value('setting_value') !== 'false') : true;

        // Student message
        if ($chatId && $siswaEnabled) {
            $msg = "🚪 <b>ABSEN PULANG BERHASIL</b> 🚪\n\n" .
                "Halo, <b>{$name}</b> 👋,\n\n" .
                "Anda telah melakukan absen pulang pada pukul <b>{$time}</b>.\n\n" .
                "ℹ️ Detail kehadiran hari ini ({$dateText}):\n" .
                "• Jam Masuk: {$jamMasuk}\n" .
                "• Jam Pulang: {$time}\n" .
                "• Durasi Hadir: {$hours} jam {$mins} menit\n" .
                "• Izin Gerbang Oleh: {$authorizer}\n\n" .
                "<i>Hati-hati di jalan saat pulang!</i> 🏠";
            $this->dispatchJob($token, $chatId, $msg, $schoolId);
        }

        // Parent message
        if ($chatIdOrtu && $ortuEnabled) {
            $msgOrtu = "🔔 <b>Laporan Absensi Pulang</b> 🔔\n\n" .
                "Bapak/Ibu Orang Tua/Wali dari <b>{$name}</b>,\n\n" .
                "Menginfokan bahwa putra/putri Anda telah melakukan absen pulang sekolah pada pukul <b>{$time}</b>.\n\n" .
                "ℹ️ Detail kehadiran hari ini ({$dateText}):\n" .
                "• Jam Masuk: {$jamMasuk}\n" .
                "• Jam Pulang: {$time}\n" .
                "• Durasi Hadir: {$hours} jam {$mins} menit\n" .
                "• Izin Gerbang Oleh: {$authorizer}";
            $this->dispatchJob($token, $chatIdOrtu, $msgOrtu, $schoolId);
        }
    }

    public function sendKegiatanCheckIn(
        string $namaSiswa,
        string $namaKegiatan,
        string $jam,
        string $tanggal,
        int $schoolId,
        ?string $chatIdSiswa = null,
        ?string $chatIdOrtu = null
    ): void {
        if (!$chatIdSiswa && !$chatIdOrtu) {
            return;
        }

        $school = School::find($schoolId);
        if (!$school || !$school->telegram_enabled || !$school->telegram_bot_token) {
            return;
        }
        $token = $school->telegram_bot_token;

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_ortu')->value('setting_value') !== 'false') : true;

        if ($chatIdSiswa && $siswaEnabled) {
            $msg = "✨ <b>KEHADIRAN KEGIATAN BERHASIL</b> ✨\n\n" .
                "Halo, <b>{$namaSiswa}</b> 👋,\n\n" .
                "Anda terdaftar hadir dalam kegiatan berikut:\n" .
                "• Kegiatan: <b>{$namaKegiatan}</b>\n" .
                "• Waktu: {$jam}\n" .
                "• Tanggal: {$tanggal}\n\n" .
                "<i>Terima kasih telah mengikuti kegiatan dengan tertib.</i> 👍";
            $this->dispatchJob($token, $chatIdSiswa, $msg, $schoolId);
        }

        if ($chatIdOrtu && $ortuEnabled) {
            $msgOrtu = "🔔 <b>Laporan Kehadiran Kegiatan</b> 🔔\n\n" .
                "Bapak/Ibu Orang Tua/Wali dari <b>{$namaSiswa}</b>,\n\n" .
                "Menginfokan bahwa putra/putri Anda telah terdaftar hadir mengikuti kegiatan sekolah:\n" .
                "• Kegiatan: <b>{$namaKegiatan}</b>\n" .
                "• Waktu: {$jam}\n" .
                "• Tanggal: {$tanggal}";
            $this->dispatchJob($token, $chatIdOrtu, $msgOrtu, $schoolId);
        }
    }

    private function dispatchJob(string $token, string $chatId, string $text, int $schoolId): void
    {
        // Append signature if school name is available
        $school = School::find($schoolId);
        if ($school && !empty($school->name)) {
            $signature = "<b>" . trim($school->name) . "</b>";
            if (!str_contains($text, $signature)) {
                $text = rtrim($text) . "\n\n" . $signature;
            }
        }

        SendTelegramMessageJob::dispatch($token, $chatId, $text, $schoolId);
    }

    private function parseLateDuration(?string $keterangan): array
    {
        if (empty($keterangan)) return [0, 0];

        $hours   = 0;
        $minutes = 0;

        if (preg_match('/(\d+)\s*jam/', $keterangan, $m)) {
            $hours = (int) $m[1];
        }

        if (preg_match('/(\d+)\s*menit/', $keterangan, $m)) {
            $minutes = (int) $m[1];
        }

        return [$hours, $minutes];
    }
}
