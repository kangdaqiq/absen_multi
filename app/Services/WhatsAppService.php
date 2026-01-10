<?php

namespace App\Services;

use App\Models\MessageQueue;
use App\Services\WhatsAppMessageTemplates;

class WhatsAppService
{
    public function sendEnrollSuccess($name, $phone, $uid, $type = 'Kartu RFID', $phoneOrtu = null)
    {
        // Skip if both phone numbers are empty
        if (!$phone && !$phoneOrtu)
            return;

        // Send to student if phone exists
        if ($phone) {
            $msg = "✨ *PENDAFTARAN BERHASIL* ✨\n\n" .
                "Assalamualaikum, *{$name}* 👋,\n\n" .
                "Kartu/Perangkat *{$type}* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n" .
                "🆔 ID Kartu: `{$uid}`\n" .
                "📅 Tanggal: " . now()->translatedFormat('l, d F Y') . "\n\n" .
                "_Terima kasih telah melakukan registrasi._ 🙏";
            $this->queueMessage($phone, $msg);
        }

        // Send to parent if phone number exists
        if ($phoneOrtu) {
            $msgOrtu = "✨ *PENDAFTARAN BERHASIL* ✨\n\n" .
                "Assalamualaikum, Anak Anda, *{$name}* 👋,\n\n" .
                "Kartu/Perangkat *{$type}* telah berhasil didaftarkan ke sistem absensi sekolah.\n\n" .
                "🆔 ID Kartu: `{$uid}`\n" .
                "📅 Tanggal: " . now()->translatedFormat('l, d F Y') . "\n\n" .
                "_Terima kasih telah melakukan registrasi._ 🙏";
            $this->queueMessage($phoneOrtu, $msgOrtu);
        }
    }



    public function sendCheckIn($name, $phone, $time, $status, $keterangan = null, $phoneOrtu = null)
    {
        // Skip if both phone numbers are empty
        if (!$phone && !$phoneOrtu)
            return;

        // Determine if late based on keterangan
        $isLate = !empty($keterangan);

        // Get student's class (we'll need to pass this from controller)
        // For now, use a placeholder or extract from keterangan if available
        $kelas = '-'; // This should be passed from controller

        // Send to student if phone exists
        if ($phone) {
            if ($isLate) {
                // Extract late minutes from keterangan if possible
                preg_match('/(\d+)\s*menit/', $keterangan ?? '', $matches);
                $lateMinutes = $matches[1] ?? 0;

                $msg = WhatsAppMessageTemplates::checkInLate(
                    nama: $name,
                    jamMasuk: $time,
                    kelas: $kelas,
                    lateMinutes: (int) $lateMinutes
                );
            } else {
                $msg = WhatsAppMessageTemplates::checkIn(
                    nama: $name,
                    jamMasuk: $time,
                    kelas: $kelas,
                    status: $status
                );
            }
            $this->queueMessage($phone, $msg);
        }

        // Send to parent if phone number exists
        if ($phoneOrtu) {
            // Use same template for parent
            if ($isLate) {
                preg_match('/(\d+)\s*menit/', $keterangan ?? '', $matches);
                $lateMinutes = $matches[1] ?? 0;

                $msgOrtu = WhatsAppMessageTemplates::checkInLate(
                    nama: $name,
                    jamMasuk: $time,
                    kelas: $kelas,
                    lateMinutes: (int) $lateMinutes
                );
            } else {
                $msgOrtu = WhatsAppMessageTemplates::checkIn(
                    nama: $name,
                    jamMasuk: $time,
                    kelas: $kelas,
                    status: $status
                );
            }
            $this->queueMessage($phoneOrtu, $msgOrtu);
        }
    }

    public function sendCheckOut($name, $phone, $time, $hours, $mins, $authorizer, $jamMasuk = '-', $phoneOrtu = null)
    {
        // Skip if both phone numbers are empty
        if (!$phone && !$phoneOrtu)
            return;

        // Send to student if phone exists
        if ($phone) {
            $msg = WhatsAppMessageTemplates::checkOut(
                nama: $name,
                jamMasuk: $jamMasuk,
                jamPulang: $time,
                hours: $hours,
                minutes: $mins,
                authorizedBy: $authorizer
            );
            $this->queueMessage($phone, $msg);
        }

        // Send to parent if phone number exists
        if ($phoneOrtu) {
            $msgOrtu = WhatsAppMessageTemplates::checkOut(
                nama: $name,
                jamMasuk: $jamMasuk,
                jamPulang: $time,
                hours: $hours,
                minutes: $mins,
                authorizedBy: $authorizer
            );
            $this->queueMessage($phoneOrtu, $msgOrtu);
        }
    }

    private function queueMessage($phone, $message)
    {
        $originalPhone = $phone;
        $phone = $this->formatPhone($phone);

        if ($phone) {
            try {
                MessageQueue::create([
                    'phone_number' => $phone,
                    'message' => $message,
                    'status' => 'pending',
                    'created_at' => now()
                ]);
            } catch (\Exception $e) {
                // Log failure to API Log so user can see it
                \App\Models\ApiLog::create([
                    'action' => 'wa_error',
                    'success' => false,
                    'message' => 'DB Error: ' . $e->getMessage(),
                    'created_at' => now()
                ]);
            }
        } else {
            // Log skipped empty phone
            \App\Models\ApiLog::create([
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

        return $phone . '@s.whatsapp.net';
    }
}
