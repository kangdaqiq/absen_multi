<?php

namespace App\Services;

use App\Models\MessageQueue;
use App\Services\WhatsAppMessageTemplates;

class WhatsAppService
{
    public function sendEnrollSuccess($name, $phone, $uid, $schoolId, $type = 'Kartu RFID', $phoneOrtu = null)
    {
        // Skip if both phone numbers are empty
        if (!$phone && !$phoneOrtu)
            return;

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_ortu')->value('setting_value') !== 'false') : true;

        // Send to student if phone exists
        if ($phone && $siswaEnabled) {
            $msg = "✨ *PENDAFTARAN BERHASIL* ✨\n\n" .
                "Halo, *{$name}* 👋,\n\n" .
                "Kartu/Perangkat *{$type}* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n" .
                "🆔 ID Kartu: `{$uid}`\n" .
                "📅 Tanggal: " . now()->translatedFormat('l, d F Y') . "\n\n" .
                "_Terima kasih telah melakukan registrasi._ 🙏";
            $this->queueMessage($phone, $msg, $schoolId);
        }

        // Send to parent if phone number exists
        if ($phoneOrtu && $ortuEnabled) {
            $msgOrtu = "✨ *PENDAFTARAN BERHASIL* ✨\n\n" .
                "Halo, Anak Anda, *{$name}* 👋,\n\n" .
                "Kartu/Perangkat *{$type}* telah berhasil didaftarkan ke sistem absensi sekolah.\n\n" .
                "🆔 ID Kartu: `{$uid}`\n" .
                "📅 Tanggal: " . now()->translatedFormat('l, d F Y') . "\n\n" .
                "_Terima kasih telah melakukan registrasi._ 🙏";
            $this->queueMessage($phoneOrtu, $msgOrtu, $schoolId);
        }
    }



    public function sendCheckIn($name, $phone, $time, $status, $schoolId, $keterangan = null, $phoneOrtu = null, $kelas = '-')
    {
        // Skip if both phone numbers are empty
        if (!$phone && !$phoneOrtu)
            return;

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_ortu')->value('setting_value') !== 'false') : true;

        // Map short status to readable status
        $statusMap = [
            'H' => 'Hadir',
            'A' => 'Alpha',
            'I' => 'Izin',
            'S' => 'Sakit',
            'B' => 'Bolos',
            'T' => 'Terlambat'
        ];
        $readableStatus = $statusMap[strtoupper($status)] ?? $status;

        // Determine if late based on keterangan
        $isLate = !empty($keterangan);

        // Send to student if phone exists
        if ($phone && $siswaEnabled) {
            if ($isLate) {
                // Parse durasi dari keterangan: "Telat 1 jam 30 menit" atau "Telat 30 menit"
                [$lateHours, $lateMinutes] = $this->parseLateDuration($keterangan);

                $msg = WhatsAppMessageTemplates::checkInLate(
                     nama: $name,
                     jamMasuk: $time,
                     kelas: $kelas,
                     lateHours: $lateHours,
                     lateMinutes: $lateMinutes
                );
            } else {
                $msg = WhatsAppMessageTemplates::checkIn(
                     nama: $name,
                     jamMasuk: $time,
                     kelas: $kelas,
                     status: $readableStatus
                );
            }
            $this->queueMessage($phone, $msg, $schoolId);
        }

        // Send to parent if phone number exists
        if ($phoneOrtu && $ortuEnabled) {
            if ($isLate) {
                [$lateHours, $lateMinutes] = $this->parseLateDuration($keterangan);

                $msgOrtu = WhatsAppMessageTemplates::checkInLateParent(
                     nama: $name,
                     jamMasuk: $time,
                     kelas: $kelas,
                     lateHours: $lateHours,
                     lateMinutes: $lateMinutes
                );
            } else {
                $msgOrtu = WhatsAppMessageTemplates::checkInParent(
                     nama: $name,
                     jamMasuk: $time,
                     kelas: $kelas,
                     status: $readableStatus
                );
            }
            $this->queueMessage($phoneOrtu, $msgOrtu, $schoolId);
        }
    }

    /**
     * Parse durasi keterlambatan dari string keterangan.
     * Contoh: "Telat 1 jam 30 menit" -> [1, 30]
     *         "Telat 30 menit"        -> [0, 30]
     *
     * @return array [hours, minutes]
     */
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

    public function sendCheckOut($name, $phone, $time, $hours, $mins, $authorizer, $schoolId, $jamMasuk = '-', $phoneOrtu = null, $tanggal = null)
    {
        // Skip if both phone numbers are empty
        if (!$phone && !$phoneOrtu)
            return;

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_ortu')->value('setting_value') !== 'false') : true;

        // Send to student if phone exists
        if ($phone && $siswaEnabled) {
            $msg = WhatsAppMessageTemplates::checkOut(
                nama: $name,
                jamMasuk: $jamMasuk,
                jamPulang: $time,
                hours: $hours,
                minutes: $mins,
                authorizedBy: $authorizer,
                tanggal: $tanggal
            );
            $this->queueMessage($phone, $msg, $schoolId);
        }

        // Send to parent if phone number exists
        if ($phoneOrtu && $ortuEnabled) {
            $msgOrtu = WhatsAppMessageTemplates::checkOutParent(
                nama: $name,
                jamMasuk: $jamMasuk,
                jamPulang: $time,
                hours: $hours,
                minutes: $mins,
                authorizedBy: $authorizer,
                tanggal: $tanggal
            );
            $this->queueMessage($phoneOrtu, $msgOrtu, $schoolId);
        }
    }

    /**
     * Kirim notifikasi kehadiran kegiatan ke siswa dan/atau ortu.
     */
    public function sendKegiatanCheckIn(
        string $namaSiswa,
        string $namaKegiatan,
        string $jam,
        string $tanggal,
        int $schoolId,
        ?string $phoneSiswa = null,
        ?string $phoneOrtu = null
    ): void {
        if (!$phoneSiswa && !$phoneOrtu) return;

        $siswaEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_siswa')->value('setting_value') !== 'false') : true;
        $ortuEnabled = $schoolId ? (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_ortu')->value('setting_value') !== 'false') : true;

        if ($phoneSiswa && $siswaEnabled) {
            $msg = WhatsAppMessageTemplates::kegiatanCheckIn(
                nama: $namaSiswa,
                namaKegiatan: $namaKegiatan,
                jam: $jam,
                tanggal: $tanggal,
                isOrtu: false
            );
            $this->queueMessage($phoneSiswa, $msg, $schoolId);
        }

        if ($phoneOrtu && $ortuEnabled) {
            $msgOrtu = WhatsAppMessageTemplates::kegiatanCheckIn(
                nama: $namaSiswa,
                namaKegiatan: $namaKegiatan,
                jam: $jam,
                tanggal: $tanggal,
                isOrtu: true
            );
            $this->queueMessage($phoneOrtu, $msgOrtu, $schoolId);
        }
    }

    public function sendTestMessage($phone, $message, $schoolId = null)
    {
        if (!$phone || empty(trim($message))) return;
        $this->queueMessage($phone, $message, $schoolId);
    }

    private function queueMessage($phone, $message, $schoolId = null, ?int $delaySeconds = null)
    {
        $originalPhone = $phone;
        $phone = $this->formatPhone($phone);

        if ($phone) {
            try {
                // Tunda pengiriman secara acak 1–5 menit untuk meniru perilaku manusia
                // dan menghindari burst sending yang terdeteksi sebagai bot oleh WhatsApp.
                if ($delaySeconds === null) {
                    $delaySeconds = rand(60, 300); // 1–5 menit random
                }

                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $phone,
                    'message'      => $message,
                    'status'       => 'pending',
                    'scheduled_at' => now()->addSeconds($delaySeconds),
                    'created_at'   => now(),
                ]);
            } catch (\Exception $e) {
                // Log failure to API Log so user can see it
                \App\Models\ApiLog::create([
                    'school_id' => $schoolId,
                    'action' => 'wa_error',
                    'success' => false,
                    'message' => 'DB Error: ' . $e->getMessage(),
                    'created_at' => now()
                ]);
            }
        } else {
            // Log skipped empty phone
            \App\Models\ApiLog::create([
                'school_id' => $schoolId,
                'action' => 'wa_skip',
                'success' => false,
                'message' => "Phone number invalid/empty. Original: '$originalPhone'",
                'created_at' => now()
            ]);
        }
    }

    private function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone))
            return null;
        if (substr($phone, 0, 1) === '0')
            $phone = '62' . substr($phone, 1);
        elseif (substr($phone, 0, 2) !== '62')
            $phone = '62' . $phone;

        return $phone;
    }
}
