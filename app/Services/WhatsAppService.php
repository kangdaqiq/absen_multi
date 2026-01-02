<?php

namespace App\Services;

use App\Models\MessageQueue;

class WhatsAppService
{
    public function sendEnrollSuccess($name, $phone, $uid, $type = 'Kartu RFID')
    {
        if (!$phone)
            return;

        $msg = "✨ *PENDAFTARAN BERHASIL* ✨\n\n" .
            "Halo *{$name}* 👋,\n\n" .
            "Kartu/Perangkat *{$type}* Anda telah berhasil didaftarkan ke sistem absensi sekolah.\n\n" .
            "🆔 ID Kartu: `{$uid}`\n" .
            "📅 Tanggal: " . now()->translatedFormat('l, d F Y') . "\n\n" .
            "_Terima kasih telah melakukan registrasi._ 🙏";

        $this->queueMessage($phone, $msg);
    }



    public function sendCheckIn($name, $phone, $time, $status, $keterangan = null)
    {
        if (!$phone)
            return;

        $statusText = $keterangan ? 'Terlambat' : 'Tepat Waktu';
        $ketText = $keterangan ? "\n📝 Keterangan: {$keterangan}" : "";

        $msg = "✅ Absen Masuk Berhasil\n\n" .
            "Halo *{$name}*,\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "� Jam Masuk: {$time}\n" .
            "📊 Status: {$statusText}{$ketText}\n\n" .
            "Selamat belajar! 📚\n\n" .
            "Jangan lupa absen pulang ya!";

        $this->queueMessage($phone, $msg);
    }

    public function sendCheckOut($name, $phone, $time, $hours, $mins, $authorizer, $jamMasuk = '-')
    {
        if (!$phone)
            return;

        $msg = "🏠 Absen Pulang Berhasil\n\n" .
            "Halo *{$name}*,\n\n" .
            "📍 Jam Masuk: {$jamMasuk}\n" .
            "� Jam Pulang: {$time}\n" .
            "⏱️ Durasi: {$hours} jam {$mins} menit\n" .
            "� Diizinkan oleh: {$authorizer}\n\n" .
            "Terima kasih telah mengikuti kegiatan hari ini.\n\n" .
            "Hati-hati di jalan! 🙏";

        $this->queueMessage($phone, $msg);
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
