<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AbsensiGuru;
use App\Models\MessageQueue;
use App\Models\TelegramLog;
use Carbon\Carbon;

class NotificationDeliveryService
{
    /**
     * Hitung total absen terkini hari ini (Siswa + Guru).
     */
    public function getTodayAbsenCount(?int $schoolId = null): array
    {
        $today = Carbon::today();

        // 1. Absensi Siswa Hari Ini
        $siswaQuery = Attendance::whereDate('tanggal', $today);
        if ($schoolId !== null) {
            $siswaQuery->whereHas('student', function ($sub) use ($schoolId) {
                $sub->where('school_id', $schoolId);
            });
        }
        $siswaCount = $siswaQuery->count();

        // 2. Absensi Guru Hari Ini
        $guruQuery = AbsensiGuru::whereDate('tanggal', $today);
        if ($schoolId !== null) {
            $guruQuery->where('school_id', $schoolId);
        }
        $guruCount = $guruQuery->count();

        $totalCount = $siswaCount + $guruCount;

        return [
            'total' => $totalCount,
            'siswa' => $siswaCount,
            'guru'  => $guruCount,
        ];
    }

    /**
     * Ambil statistik delivery notifikasi (WhatsApp & Telegram) hari ini
     * dibandingkan dengan jumlah absensi terkini hari ini.
     *
     * @param int|null $schoolId
     * @param int|null $customAbsenCount
     * @return array
     */
    public function getTodayDeliveryStats(?int $schoolId = null, ?int $customAbsenCount = null): array
    {
        $today = Carbon::today();

        // Hitung absen terkini hari ini jika tidak ditentukan
        $absenInfo = $this->getTodayAbsenCount($schoolId);
        $countAbsen = $customAbsenCount !== null ? $customAbsenCount : $absenInfo['total'];

        // 1. Query WhatsApp (MessageQueue) Hari Ini
        $waQuery = MessageQueue::whereDate('created_at', $today);
        if ($schoolId !== null) {
            $waQuery->where('school_id', $schoolId);
        }

        $waStats = (clone $waQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
            ")
            ->first();

        $waTotal   = (int) ($waStats->total ?? 0);
        $waSent    = (int) ($waStats->sent_count ?? 0);
        $waPending = (int) ($waStats->pending_count ?? 0);
        $waFailed  = (int) ($waStats->failed_count ?? 0);

        $waPercentOfAbsen = $countAbsen > 0 ? round(($waSent / $countAbsen) * 100, 1) : 0.0;
        $waSuccessRate    = $waTotal > 0 ? round(($waSent / $waTotal) * 100, 1) : 0.0;

        // 2. Query Telegram (TelegramLog) Hari Ini
        $teleQuery = TelegramLog::whereDate('created_at', $today);
        if ($schoolId !== null) {
            $teleQuery->where('school_id', $schoolId);
        }

        $teleStats = (clone $teleQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
            ")
            ->first();

        $teleTotal  = (int) ($teleStats->total ?? 0);
        $teleSent   = (int) ($teleStats->sent_count ?? 0);
        $teleFailed = (int) ($teleStats->failed_count ?? 0);

        $telePercentOfAbsen = $countAbsen > 0 ? round(($teleSent / $countAbsen) * 100, 1) : 0.0;
        $teleSuccessRate    = $teleTotal > 0 ? round(($teleSent / $teleTotal) * 100, 1) : 0.0;

        // 3. Ringkasan Gabungan
        $totalNotif = $waTotal + $teleTotal;
        $totalSent  = $waSent + $teleSent;
        $totalFailed = $waFailed + $teleFailed;
        $overallSuccessRate = $totalNotif > 0 ? round(($totalSent / $totalNotif) * 100, 1) : 0.0;
        $overallPercentOfAbsen = $countAbsen > 0 ? round(($totalSent / $countAbsen) * 100, 1) : 0.0;

        return [
            'absen_count' => $countAbsen,
            'absen_detail' => $absenInfo,
            'wa' => [
                'total'            => $waTotal,
                'sent'             => $waSent,
                'pending'          => $waPending,
                'failed'           => $waFailed,
                'percent_of_absen' => $waPercentOfAbsen,
                'success_rate'     => $waSuccessRate,
            ],
            'telegram' => [
                'total'            => $teleTotal,
                'sent'             => $teleSent,
                'failed'           => $teleFailed,
                'percent_of_absen' => $telePercentOfAbsen,
                'success_rate'     => $teleSuccessRate,
            ],
            'overall' => [
                'total_notif'      => $totalNotif,
                'total_sent'       => $totalSent,
                'total_failed'     => $totalFailed,
                'percent_of_absen' => $overallPercentOfAbsen,
                'success_rate'     => $overallSuccessRate,
            ]
        ];
    }
}
