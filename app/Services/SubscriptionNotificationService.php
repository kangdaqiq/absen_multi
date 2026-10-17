<?php

namespace App\Services;

use App\Models\MessageQueue;
use App\Models\Package;
use App\Models\School;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionNotificationService
{
    /**
     * Get or create an unpaid pending subscription for a school.
     */
    public function getOrCreatePendingSubscription(School $school, ?Package $package = null, string $billingCycle = 'monthly'): Subscription
    {
        $existing = Subscription::with('package')
            ->where('school_id', $school->id)
            ->where('status', 'unpaid')
            ->latest()
            ->first();

        if ($existing) {
            $existing->ensureInvoiceDetails();
            return $existing;
        }

        if (!$package) {
            $package = Package::where('is_active', true)
                ->where('student_limit', '>=', $school->student_limit ?? 0)
                ->orderBy('student_limit', 'asc')
                ->first()
                ?? Package::where('is_active', true)->orderBy('price_monthly', 'asc')->first();
        }

        $basePrice = $billingCycle === 'yearly'
            ? (float) ($package?->price_yearly ?? 0)
            : (float) ($package?->price_monthly ?? 0);

        // Generate unique code for QRIS auto-matching
        $uniqueCode = 0;
        $totalAmount = $basePrice;

        if ($basePrice > 0) {
            do {
                $uniqueCode = rand(100, 999);
                $totalAmount = $basePrice + $uniqueCode;
                $exists = Subscription::where('status', 'unpaid')
                    ->where('amount', $totalAmount)
                    ->exists();
            } while ($exists);
        }

        $now = now();
        $startedAt = $school->expired_at
            ? clone $school->expired_at
            : $now;

        $expiredAt = $billingCycle === 'yearly'
            ? (clone $startedAt)->addYear()
            : (clone $startedAt)->addMonth();

        $subscription = Subscription::create([
            'school_id'      => $school->id,
            'package_id'     => $package?->id,
            'amount'         => $totalAmount,
            'unique_code'    => $uniqueCode,
            'status'         => 'unpaid',
            'billing_cycle'  => $billingCycle,
            'payment_method' => 'qris',
            'started_at'     => $startedAt,
            'expired_at'     => $expiredAt,
        ]);

        $subscription->ensureInvoiceDetails();
        return $subscription;
    }

    /**
     * Check if a specific WhatsApp device is connected on GOWA API.
     */
    public function isDeviceConnected(string $deviceId): bool
    {
        $baseUrl = rtrim(config('services.whatsapp.base_url', env('GOWA_API_BASE_URL', env('WA_API_BASE_URL', 'http://localhost:3000'))), '/');
        $user    = config('services.whatsapp.user', env('GOWA_API_USER', env('WA_API_USER', 'admin')));
        $pass    = config('services.whatsapp.pass', env('GOWA_API_PASS', env('WA_API_PASS', 'opowae')));

        try {
            $res = Http::timeout(5)
                ->withBasicAuth($user, $pass)
                ->withHeaders(['X-Device-Id' => $deviceId])
                ->get("{$baseUrl}/app/status");

            if ($res->successful()) {
                $data = $res->json();
                $isLoggedIn  = $data['results']['is_logged_in'] ?? false;
                $isConnected = $data['results']['is_connected'] ?? false;
                return ($isLoggedIn && $isConnected);
            }
        } catch (\Exception $e) {
            Log::warning("GOWA status check error for device {$deviceId}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Resolve the primary recipient phone for a school (operator, school phone, or admin user).
     */
    public function getSchoolAdminPhone(School $school): ?string
    {
        $phoneRaw = $school->operator_phone ?: $school->phone;
        if (empty($phoneRaw)) {
            $adminUser = $school->users()->where('role', 'admin')->first();
            $phoneRaw = $adminUser?->guru?->no_wa;
        }

        if (empty($phoneRaw)) {
            return null;
        }

        return $this->formatPhone($phoneRaw);
    }

    /**
     * Determine Sender WA Device (SuperAdmin vs School fallback).
     */
    public function determineSenderDevice(School $school): array
    {
        $superAdminConnected = $this->isDeviceConnected('superadmin');
        if ($superAdminConnected) {
            return [
                'school_id' => null,
                'label'     => 'WA SuperAdmin (Pusat)',
            ];
        }

        $schoolConnected = $this->isDeviceConnected((string) $school->id);
        if ($schoolConnected || $school->wa_enabled) {
            return [
                'school_id' => $school->id,
                'label'     => "WA Sekolah ({$school->name}) [Fallback]",
            ];
        }

        return [
            'school_id' => null,
            'label'     => 'WA SuperAdmin (Pending Reconnect)',
        ];
    }

    /**
     * Send renewal notification with direct public invoice link to school admin.
     */
    public function sendRenewalInvoiceWhatsApp(School $school, ?Subscription $subscription = null, ?int $daysRemaining = null): array
    {
        $phone = $this->getSchoolAdminPhone($school);
        if (!$phone) {
            Log::warning("Cannot send renewal invoice to {$school->name}: No operator/admin phone.");
            return [
                'success' => false,
                'message' => "Nomor WhatsApp admin/operator untuk sekolah {$school->name} tidak ditemukan atau formatnya tidak valid."
            ];
        }

        if (!$subscription) {
            $subscription = $this->getOrCreatePendingSubscription($school);
        } else {
            $subscription->ensureInvoiceDetails();
        }

        $invoiceUrl = $subscription->public_invoice_url;
        $packageName = $subscription->package?->name ?? 'Paket Langganan Sekolah';
        $totalFormatted = 'Rp ' . number_format($subscription->amount, 0, ',', '.');
        $billingCycleText = $subscription->billing_cycle === 'yearly' ? '1 Tahun' : '1 Bulan';

        $expiryInfo = '';
        if ($school->expired_at) {
            $formattedDate = $school->expired_at->translatedFormat('d F Y');
            if ($daysRemaining !== null) {
                if ($daysRemaining > 0) {
                    $expiryInfo = "akan berakhir pada *{$formattedDate}* (Sisa {$daysRemaining} Hari)";
                } elseif ($daysRemaining === 0) {
                    $expiryInfo = "berakhir *HARI INI* (*{$formattedDate}*)";
                } else {
                    $expiryInfo = "telah *KEDALUWARSA* sejak {$formattedDate}";
                }
            } else {
                $expiryInfo = "berakhir pada *{$formattedDate}*";
            }
        } else {
            $expiryInfo = "memerlukan aktivasi paket langganan";
        }

        $message = "🔔 *TAGIHAN PERPANJANGAN SISTEM ABSENSI* 🔔\n\n" .
            "Yth. Pengelola / Admin *{$school->name}*,\n\n" .
            "Masa aktif langganan sistem absensi sekolah Anda {$expiryInfo}.\n\n" .
            "📋 *Rincian Tagihan:*\n" .
            "• No. Invoice: `{$subscription->invoice_number}`\n" .
            "• Paket: *{$packageName}* ({$billingCycleText})\n" .
            "• Total Pembayaran: *{$totalFormatted}*\n\n" .
            "💳 *Link Pembayaran:*\n" .
            "👉 {$invoiceUrl}\n\n" .
            "_Pembayaran via QRIS (BCA, Mandiri, BRI, BNI, Dana, GoPay, OVO, ShopeePay) langsung diverifikasi sistem secara instan._\n\n" .
            "Terima kasih atas kerja samanya. 🙏";

        $sender = $this->determineSenderDevice($school);

        // Create Message Queue with high priority (priority 20) and bypass_last_seen
        $mq = new MessageQueue([
            'school_id'    => $sender['school_id'],
            'phone_number' => $phone,
            'message'      => $message,
            'status'       => 'pending',
            'scheduled_at' => now(), // send immediately
            'priority'     => 20,    // high priority for invoice (bypasses grace period filter)
            'created_at'   => now(),
        ]);
        $mq->bypass_last_seen = true;
        $mq->save();

        Log::info("Renewal invoice queued for {$school->name} ({$phone}) via {$sender['label']}. Invoice: {$invoiceUrl}");

        return [
            'success'        => true,
            'message'        => "Invoice berhasil dikirim ke WhatsApp Admin {$school->name} ({$phone}) via {$sender['label']}.",
            'invoice_url'    => $invoiceUrl,
            'sender'         => $sender['label'],
            'invoice_number' => $subscription->invoice_number,
            'total_amount'   => $subscription->amount,
        ];
    }

    /**
     * Send payment success notification and receipt via WhatsApp to school admin.
     */
    public function sendPaymentSuccessWhatsApp(Subscription $subscription): array
    {
        $school = $subscription->school;
        if (!$school) {
            $subscription->load('school');
            $school = $subscription->school;
        }

        if (!$school) {
            Log::warning("Cannot send payment success notification: School not found for subscription #{$subscription->id}");
            return [
                'success' => false,
                'message' => "Sekolah untuk langganan ID #{$subscription->id} tidak ditemukan."
            ];
        }

        $phone = $this->getSchoolAdminPhone($school);
        if (!$phone) {
            Log::warning("Cannot send payment success notification to {$school->name}: No operator/admin phone.");
            return [
                'success' => false,
                'message' => "Nomor WhatsApp admin/operator untuk sekolah {$school->name} tidak ditemukan atau formatnya tidak valid."
            ];
        }

        $subscription->ensureInvoiceDetails();
        $subscription->loadMissing('package');

        $packageName = $subscription->package?->name ?? 'Paket Langganan Sekolah';
        $totalFormatted = 'Rp ' . number_format($subscription->amount, 0, ',', '.');
        $billingCycleText = $subscription->billing_cycle === 'yearly' ? '1 Tahun' : '1 Bulan';

        $paymentMethod = strtoupper($subscription->payment_method ?? 'QRIS');
        if ($paymentMethod === 'MANUAL') {
            $paymentMethod = 'Transfer Bank (Manual)';
        }

        $paidAt = $subscription->paid_at
            ? $subscription->paid_at->translatedFormat('d F Y H:i')
            : now()->translatedFormat('d F Y H:i');

        $expiredAtFormatted = $school->expired_at
            ? $school->expired_at->translatedFormat('d F Y')
            : ($subscription->expired_at ? $subscription->expired_at->translatedFormat('d F Y') : '-');

        $invoiceUrl = $subscription->public_invoice_url;

        $message = "✅ *PEMBAYARAN PERPANJANGAN BERHASIL* ✅\n\n" .
            "Yth. Pengelola / Admin *{$school->name}*,\n\n" .
            "Pembayaran perpanjangan langganan sistem absensi sekolah Anda telah *berhasil diverifikasi* dan seluruh layanan aktif kembali.\n\n" .
            "📋 *Rincian Pembayaran:*\n" .
            "• No. Invoice: `{$subscription->invoice_number}`\n" .
            "• Paket: *{$packageName}* ({$billingCycleText})\n" .
            "• Total Pembayaran: *{$totalFormatted}*\n" .
            "• Metode: *{$paymentMethod}*\n" .
            "• Waktu Bayar: *{$paidAt} WIB*\n\n" .
            "🗓️ *Masa Aktif Layanan Baru:*\n" .
            "Aktif s/d: *{$expiredAtFormatted}*\n\n" .
            "🧾 *Kuitansi Elektronik:*\n" .
            "👉 {$invoiceUrl}\n\n" .
            "_Seluruh fitur absensi, bot WhatsApp, dan pencatatan kehadiran sekolah Anda sudah dapat digunakan kembali secara normal._\n\n" .
            "Terima kasih atas kerja sama dan kepercayaannya! 🙏";

        $sender = $this->determineSenderDevice($school);

        // Create Message Queue with high priority (priority 20) and bypass_last_seen
        $mq = new MessageQueue([
            'school_id'    => $sender['school_id'],
            'phone_number' => $phone,
            'message'      => $message,
            'status'       => 'pending',
            'scheduled_at' => now(), // send immediately
            'priority'     => 20,    // high priority for payment receipt
            'created_at'   => now(),
        ]);
        $mq->bypass_last_seen = true;
        $mq->save();

        Log::info("Payment success WhatsApp queued for {$school->name} ({$phone}) via {$sender['label']}. Invoice: {$subscription->invoice_number}");

        return [
            'success'        => true,
            'message'        => "Kuitansi pembayaran berhasil dikirim ke WhatsApp Admin {$school->name} ({$phone}) via {$sender['label']}.",
            'invoice_url'    => $invoiceUrl,
            'sender'         => $sender['label'],
            'invoice_number' => $subscription->invoice_number,
            'total_amount'   => $subscription->amount,
        ];
    }

    private function formatPhone(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return null;
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}
