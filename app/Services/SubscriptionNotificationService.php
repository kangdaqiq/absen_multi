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
        $startedAt = ($school->expired_at && $school->expired_at > $now)
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
        $baseUrl = rtrim(env('GOWA_API_BASE_URL', 'http://localhost:3000'), '/');
        $user    = env('GOWA_API_USER', 'admin');
        $pass    = env('GOWA_API_PASS', 'jagattech');

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
     * Send renewal notification with direct public invoice link to school admin.
     * Logic:
     * 1. Check if SuperAdmin WA device is connected. If YES, send from SuperAdmin (school_id = null).
     * 2. If SuperAdmin WA is NOT connected, fallback to send from the School's own WA device (school_id = $school->id).
     */
    public function sendRenewalInvoiceWhatsApp(School $school, ?Subscription $subscription = null, ?int $daysRemaining = null): array
    {
        $phoneRaw = $school->operator_phone ?: $school->phone;
        if (empty($phoneRaw)) {
            // Check if there's an admin user with a phone
            $adminUser = $school->users()->where('role', 'admin')->first();
            $phoneRaw = $adminUser?->guru?->no_wa;
        }

        if (empty($phoneRaw)) {
            Log::warning("Cannot send renewal invoice to {$school->name}: No operator/admin phone.");
            return [
                'success' => false,
                'message' => "Nomor WhatsApp admin/operator untuk sekolah {$school->name} tidak ditemukan."
            ];
        }

        $phone = $this->formatPhone($phoneRaw);
        if (!$phone) {
            return [
                'success' => false,
                'message' => "Format nomor WhatsApp tidak valid ({$phoneRaw})."
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

        // Determine Sender WA Device (SuperAdmin vs School fallback)
        $superAdminConnected = $this->isDeviceConnected('superadmin');
        $senderSchoolId = null; // null = superadmin device
        $senderLabel = 'WA SuperAdmin (Pusat)';

        if (!$superAdminConnected) {
            // Fallback to School's own device if available
            $schoolConnected = $this->isDeviceConnected((string)$school->id);
            if ($schoolConnected || $school->wa_enabled) {
                $senderSchoolId = $school->id;
                $senderLabel = "WA Sekolah ({$school->name}) [Fallback]";
            } else {
                // Neither connected, keep as null so it sends when SuperAdmin reconnects
                $senderSchoolId = null;
                $senderLabel = 'WA SuperAdmin (Pending Reconnect)';
            }
        }

        // Create Message Queue with priority and bypass_last_seen
        $mq = new MessageQueue([
            'school_id'    => $senderSchoolId,
            'phone_number' => $phone,
            'message'      => $message,
            'status'       => 'pending',
            'scheduled_at' => now(), // send immediately
            'priority'     => 10,    // high priority for invoice
            'created_at'   => now(),
        ]);
        $mq->bypass_last_seen = true;
        $mq->save();

        Log::info("Renewal invoice queued for {$school->name} ({$phone}) via {$senderLabel}. Invoice: {$invoiceUrl}");

        return [
            'success'        => true,
            'message'        => "Invoice berhasil dikirim ke WhatsApp Admin {$school->name} ({$phone}) via {$senderLabel}.",
            'invoice_url'    => $invoiceUrl,
            'sender'         => $senderLabel,
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
