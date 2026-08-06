<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\MessageQueue;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendLastSeenReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absen:send-last-seen-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim reminder ke nomor Guru, Siswa, dan Ortu yang masa last_seen nya hampir kadaluarsa (mendekati 72 jam / 3 hari)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiryDays = (int) (Setting::where('setting_key', 'last_seen_expiry_days')->value('setting_value') ?: 3);
        $expiryHours = $expiryDays * 24;
        $warningThresholdHours = max(1, $expiryHours - 12); // 60 jam (rentang 60 - 72 jam)

        $this->info("Starting Last Seen Reminder check (Global expiry: {$expiryHours} hours [{$expiryDays} days], Threshold: {$warningThresholdHours} hours)...");

        $expiryTime = now()->subHours($expiryHours);
        $thresholdTime = now()->subHours($warningThresholdHours);
        $processedPhones = [];
        $countGuru = 0;
        $countSiswa = 0;
        $countOrtu = 0;

        // 1. GURU (Hanya yang SUDAH ADA last_seen dan masanya di rentang 60 jam s/d 72 jam)
        $gurus = Guru::whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->whereNotNull('last_seen')
            ->where('last_seen', '<=', $thresholdTime)
            ->where('last_seen', '>=', $expiryTime)
            ->get();

        foreach ($gurus as $guru) {
            $phone = $this->cleanPhone($guru->no_wa);
            if (!$phone || in_array($phone, $processedPhones) || $this->isReminderAlreadySent($phone)) {
                continue;
            }

            // Check if WA notification is enabled for this school
            if ($guru->school_id && !$this->isWaEnabled($guru->school_id, 'notification_wa_guru')) {
                continue;
            }

            $msg = "📌 *PEMBERITAHUAN AKTIVASI NOTIFIKASI ABSENSI* 📌\n\n" .
                "Halo Bapak/Ibu *{$guru->nama}* 👋,\n\n" .
                "Masa aktif interaksi WhatsApp Anda pada sistem absensi sekolah akan segera berakhir.\n\n" .
                "⚠️ *PENTING:* Agar notifikasi kehadiran & laporan sekolah tetap masuk dengan lancar, *silakan BALAS pesan ini* (contoh ketik: *OK* atau *Siap*).\n\n" .
                "_Terima kasih atas kerja samanya._ 🙏";

            $this->enqueueReminder($phone, $msg, $guru->school_id);
            $processedPhones[] = $phone;
            $countGuru++;
        }

        // 2. SISWA (Hanya yang SUDAH ADA last_seen_siswa dan masanya di rentang 60 jam s/d 72 jam)
        $siswas = Siswa::whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->whereNotNull('last_seen_siswa')
            ->where('last_seen_siswa', '<=', $thresholdTime)
            ->where('last_seen_siswa', '>=', $expiryTime)
            ->get();

        foreach ($siswas as $siswa) {
            $phone = $this->cleanPhone($siswa->no_wa);
            if (!$phone || in_array($phone, $processedPhones) || $this->isReminderAlreadySent($phone)) {
                continue;
            }

            if ($siswa->school_id && !$this->isWaEnabled($siswa->school_id, 'notification_wa_siswa')) {
                continue;
            }

            $msg = "📌 *PEMBERITAHUAN AKTIVASI NOTIFIKASI ABSENSI* 📌\n\n" .
                "Halo *{$siswa->nama}* 👋,\n\n" .
                "Masa aktif interaksi WhatsApp Anda pada sistem absensi sekolah akan segera berakhir.\n\n" .
                "⚠️ *PENTING:* Agar notifikasi kehadiran absensi sekolah Anda tetap aktif, *silakan BALAS pesan ini* (contoh ketik: *OK* atau *Siap*).\n\n" .
                "_Terima kasih._ 🙏";

            $this->enqueueReminder($phone, $msg, $siswa->school_id);
            $processedPhones[] = $phone;
            $countSiswa++;
        }

        // 3. ORTU (Hanya yang SUDAH ADA last_seen_ortu dan masanya di rentang 60 jam s/d 72 jam)
        $ortus = Siswa::whereNotNull('wa_ortu')
            ->where('wa_ortu', '!=', '')
            ->whereNotNull('last_seen_ortu')
            ->where('last_seen_ortu', '<=', $thresholdTime)
            ->where('last_seen_ortu', '>=', $expiryTime)
            ->get();

        foreach ($ortus as $siswa) {
            $phone = $this->cleanPhone($siswa->wa_ortu);
            if (!$phone || in_array($phone, $processedPhones) || $this->isReminderAlreadySent($phone)) {
                continue;
            }

            if ($siswa->school_id && !$this->isWaEnabled($siswa->school_id, 'notification_wa_ortu')) {
                continue;
            }

            $msg = "📌 *PEMBERITAHUAN AKTIVASI NOTIFIKASI ABSENSI* 📌\n\n" .
                "Halo Bapak/Ibu Orang Tua / Wali dari *{$siswa->nama}* 👋,\n\n" .
                "Masa aktif interaksi WhatsApp Anda pada sistem absensi sekolah putra/putri Anda akan segera berakhir.\n\n" .
                "⚠️ *PENTING:* Agar notifikasi kehadiran siswa tetap terkirim dan diterima di WhatsApp Anda, *silakan BALAS pesan ini* (contoh ketik: *OK* atau *Diterima*).\n\n" .
                "_Terima kasih atas perhatian dan kerja samanya._ 🙏";

            $this->enqueueReminder($phone, $msg, $siswa->school_id);
            $processedPhones[] = $phone;
            $countOrtu++;
        }

        $this->info("Last Seen Reminder finished. Sent to {$countGuru} Guru, {$countSiswa} Siswa, {$countOrtu} Ortu.");
        Log::info("Last Seen Reminder Command executed: {$countGuru} Guru, {$countSiswa} Siswa, {$countOrtu} Ortu notified.");
    }

    /**
     * Cek apakah reminder pesan sudah pernah dikirimkan ke nomor ini dalam 24 jam terakhir.
     */
    private function isReminderAlreadySent(string $phone): bool
    {
        return MessageQueue::where('phone_number', $phone)
            ->where('message', 'like', '%PEMBERITAHUAN AKTIVASI NOTIFIKASI ABSENSI%')
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();
    }

    /**
     * Enqueue reminder message bypassing last_seen check.
     */
    private function enqueueReminder(string $phone, string $message, ?int $schoolId): void
    {
        try {
            $delaySeconds = rand(60, 300); // Random delay 1-5 mins
            $mq = new MessageQueue([
                'school_id'    => $schoolId,
                'phone_number' => $phone,
                'message'      => $message,
                'status'       => 'pending',
                'scheduled_at' => now()->addSeconds($delaySeconds),
                'created_at'   => now(),
            ]);
            $mq->bypass_last_seen = true;
            $mq->save();
        } catch (\Exception $e) {
            Log::error("Failed to enqueue Last Seen Reminder for {$phone}: " . $e->getMessage());
        }
    }

    private function isWaEnabled(?int $schoolId, string $key): bool
    {
        if (!$schoolId) return true;
        try {
            return Setting::where('school_id', $schoolId)
                ->where('setting_key', $key)
                ->value('setting_value') !== 'false';
        } catch (\Exception $e) {
            return true;
        }
    }

    private function cleanPhone(?string $phone): ?string
    {
        if (empty($phone)) return null;
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) return null;
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (!str_starts_with($clean, '62')) {
            $clean = '62' . $clean;
        }
        return $clean;
    }
}
