<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Setting;
use App\Models\MessageQueue;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\School;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'absen:send-birthdays {--school= : ID sekolah tertentu (opsional)}';
    protected $description = 'Kirim ucapan selamat ulang tahun ke Guru dan Siswa yang berulang tahun hari ini via WhatsApp (berjalan otomatis pukul 00.01)';

    /** Template pesan default jika belum diatur di pengaturan */
    private const DEFAULT_SISWA = "🎂 *Selamat Ulang Tahun, {nama}!*\n\nSemoga panjang umur, sehat selalu, dan semakin berprestasi di sekolah. Teruslah semangat belajar ya! 🎉\n\nSalam hangat,\n*{sekolah}*";
    private const DEFAULT_GURU  = "🎂 *Selamat Ulang Tahun, {nama}!*\n\nSemoga panjang umur, sehat selalu, dan semakin sukses dalam mendidik generasi penerus bangsa. Terima kasih atas dedikasi Bapak/Ibu selama ini. 🎉\n\nSalam hormat,\n*{sekolah}*";

    public function handle(): void
    {
        $today = now();
        $month = $today->month;
        $day   = $today->day;

        $this->info("=== Kirim Ucapan Ulang Tahun [{$today->format('d/m/Y')}] ===");

        // Pilih sekolah: semua yang aktif, atau yang diminta via opsi
        $schoolIdOption = $this->option('school');

        $schoolQuery = School::where('is_active', true);
        if ($schoolIdOption) {
            $schoolQuery->where('id', $schoolIdOption);
        }

        $schools = $schoolQuery->get();

        if ($schools->isEmpty()) {
            $this->warn('Tidak ada sekolah aktif ditemukan.');
            return;
        }

        foreach ($schools as $school) {
            $this->processSchool($school, $month, $day);
        }

        $this->info('=== Selesai ===');
    }

    private function processSchool(School $school, int $month, int $day): void
    {
        $schoolId   = $school->id;
        $schoolName = $school->name ?? 'Sekolah';

        // 1. Cek apakah fitur ucapan ulang tahun diaktifkan untuk sekolah ini
        $enabled = Setting::where('school_id', $schoolId)
            ->where('setting_key', 'enable_birthday_greeting')
            ->value('setting_value');

        // Cek juga di setting global (school_id = 0)
        if ($enabled === null) {
            $enabled = Setting::where('school_id', 0)
                ->where('setting_key', 'enable_birthday_greeting')
                ->value('setting_value');
        }

        if ($enabled !== 'true') {
            $this->info("  [{$schoolName}] Fitur ucapan HUT tidak aktif. Dilewati.");
            return;
        }

        $this->info("  [{$schoolName}] Memproses ucapan HUT...");

        $telegramEnabled = $school->telegram_enabled;
        $telegramToken = $school->telegram_bot_token;

        // 2. Ambil template dari setting (dengan fallback ke default)
        $templateSiswa = $this->getTemplate($schoolId, 'birthday_greeting_siswa', self::DEFAULT_SISWA);
        $templateGuru  = $this->getTemplate($schoolId, 'birthday_greeting_guru', self::DEFAULT_GURU);

        $totalSent = 0;

        // 3. Proses Guru yang berulang tahun hari ini
        $guruBirthdays = Guru::where('school_id', $schoolId)
            ->whereNotNull('tgl_lahir')
            ->where(function($q) {
                $q->whereNotNull('no_wa')->orWhereNotNull('telegram_chat_id');
            })
            ->whereMonth('tgl_lahir', $month)
            ->whereDay('tgl_lahir', $day)
            ->get();

        foreach ($guruBirthdays as $guru) {
            $message = $this->buildMessage($templateGuru, [
                '{nama}'    => $guru->nama,
                '{sekolah}' => $schoolName,
                '{nip}'     => $guru->nip ?? '-',
            ]);

            if (!empty($guru->no_wa)) {
                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $guru->no_wa,
                    'message'      => $message,
                    'status'       => 'pending',
                    'scheduled_at' => now()->addSeconds(rand(60, 300)),
                    'created_at'   => now(),
                ]);
                $totalSent++;
                $this->info("    🎂 Guru: {$guru->nama} → {$guru->no_wa}");
            }

            // Telegram Birthday Greeting to Teacher
            if ($telegramEnabled && $telegramToken && !empty($guru->telegram_chat_id)) {
                $telegramMsg = $message;
                $telegramMsg = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $telegramMsg);
                $telegramMsg = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $telegramMsg);
                
                \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $guru->telegram_chat_id, $telegramMsg, $schoolId);
                $this->info("    🎂 Guru Telegram: {$guru->nama} → {$guru->telegram_chat_id}");
            }
        }

        $waSiswaEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_siswa')->value('setting_value') !== 'false';
        $waOrtuEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_ortu')->value('setting_value') !== 'false';
        $teleSiswaEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_siswa')->value('setting_value') !== 'false';
        $teleOrtuEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_ortu')->value('setting_value') !== 'false';

        foreach ($siswaBirthdays as $siswa) {
            $namaKelas = $siswa->kelas->nama_kelas ?? '-';

            // Kirim ke nomor siswa jika ada
            $noWaSiswa = $siswa->no_wa ?? null;
            if ($noWaSiswa && $waSiswaEnabled) {
                $message = $this->buildMessage($templateSiswa, [
                    '{nama}'    => $siswa->nama,
                    '{sekolah}' => $schoolName,
                    '{kelas}'   => $namaKelas,
                ]);

                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $noWaSiswa,
                    'message'      => $message,
                    'status'       => 'pending',
                    'scheduled_at' => now()->addSeconds(rand(60, 300)),
                    'created_at'   => now(),
                ]);

                $totalSent++;
                $this->info("    🎂 Siswa: {$siswa->nama} ({$namaKelas}) → {$noWaSiswa}");
            }

            // Telegram Birthday Greeting to Student
            if ($telegramEnabled && $telegramToken && !empty($siswa->telegram_chat_id) && $teleSiswaEnabled) {
                $messageText = isset($message) ? $message : $this->buildMessage($templateSiswa, [
                    '{nama}'    => $siswa->nama,
                    '{sekolah}' => $schoolName,
                    '{kelas}'   => $namaKelas,
                ]);

                $telegramMsg = $messageText;
                $telegramMsg = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $telegramMsg);
                $telegramMsg = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $telegramMsg);

                \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $siswa->telegram_chat_id, $telegramMsg, $schoolId);
                $this->info("    🎂 Siswa Telegram: {$siswa->nama} → {$siswa->telegram_chat_id}");
            }

            // Kirim juga ke nomor orang tua jika ada dan berbeda dengan nomor siswa
            $noWaOrtu = $siswa->wa_ortu ?? null;
            if ($noWaOrtu && $noWaOrtu !== $noWaSiswa && $waOrtuEnabled) {
                $messageOrtu = "🎂 *Selamat Ulang Tahun untuk putra/putri Anda!*\n\n"
                    . "Halo Orang Tua/Wali dari *{$siswa->nama}*,\n\n"
                    . "Hari ini adalah hari ulang tahun {$siswa->nama}. Semoga selalu sehat, ceria, dan semakin berprestasi! 🎉\n\n"
                    . "Salam hangat,\n*{$schoolName}*";

                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $noWaOrtu,
                    'message'      => $messageOrtu,
                    'status'       => 'pending',
                    'scheduled_at' => now()->addSeconds(rand(60, 300)),
                    'created_at'   => now(),
                ]);

                $totalSent++;
                $this->info("    🎂 Ortu: {$siswa->nama} → {$noWaOrtu}");
            }

            // Telegram Birthday Greeting to Parent
            if ($telegramEnabled && $telegramToken && !empty($siswa->telegram_ortu_chat_id) && $teleOrtuEnabled) {
                $telegramMsgOrtu = isset($messageOrtu) ? $messageOrtu : (
                    "🎂 <b>Selamat Ulang Tahun untuk putra/putri Anda!</b>\n\n"
                    . "Halo Orang Tua/Wali dari <b>{$siswa->nama}</b>,\n\n"
                    . "Hari ini adalah hari ulang tahun {$siswa->nama}. Semoga selalu sehat, ceria, dan semakin berprestasi! 🎉\n\n"
                    . "Salam hangat,\n<b>{$schoolName}</b>"
                );
                
                $telegramMsgOrtu = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $telegramMsgOrtu);
                $telegramMsgOrtu = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $telegramMsgOrtu);

                \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $siswa->telegram_ortu_chat_id, $telegramMsgOrtu, $schoolId);
                $this->info("    🎂 Ortu Telegram: {$siswa->nama} → {$siswa->telegram_ortu_chat_id}");
            }

            // Jika tidak ada nomor siswa, kirim hanya ke ortu
            if (!$noWaSiswa && $noWaOrtu && $waOrtuEnabled) {
                $messageOrtu = "🎂 *Selamat Ulang Tahun untuk putra/putri Anda!*\n\n"
                    . "Halo Orang Tua/Wali dari *{$siswa->nama}*,\n\n"
                    . "Hari ini adalah hari ulang tahun {$siswa->nama}. Semoga selalu sehat, ceria, dan semakin berprestasi! 🎉\n\n"
                    . "Salam hangat,\n*{$schoolName}*";

                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $noWaOrtu,
                    'message'      => $messageOrtu,
                    'status'       => 'pending',
                    'scheduled_at' => now()->addSeconds(rand(60, 300)),
                    'created_at'   => now(),
                ]);

                $totalSent++;
                $this->info("    🎂 Ortu (via wa_ortu): {$siswa->nama} → {$noWaOrtu}");
            }
        }

        $this->info("  [{$schoolName}] Total pesan antri: {$totalSent} (Guru: {$guruBirthdays->count()}, Siswa: {$siswaBirthdays->count()})");
    }

    /**
     * Ambil template dari DB setting, fallback ke default jika kosong.
     */
    private function getTemplate(int $schoolId, string $key, string $default): string
    {
        // Cek setting per sekolah
        $template = Setting::where('school_id', $schoolId)
            ->where('setting_key', $key)
            ->value('setting_value');

        // Fallback ke setting global
        if (empty($template)) {
            $template = Setting::where('school_id', 0)
                ->where('setting_key', $key)
                ->value('setting_value');
        }

        return !empty($template) ? $template : $default;
    }

    /**
     * Ganti placeholder dalam template dengan nilai aktual.
     */
    private function buildMessage(string $template, array $replacements): string
    {
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
