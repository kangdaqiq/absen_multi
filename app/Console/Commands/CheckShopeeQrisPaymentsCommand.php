<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\ShopeePayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckShopeeQrisPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qris:check-shopee {--hours=24 : Jam ke belakang untuk cek tagihan belum lunas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek mutasi pembayaran QRIS ShopeePay secara otomatis untuk tagihan langganan sekolah yang berstatus unpaid';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!ShopeePayService::isAutomaticMode()) {
            $this->comment('Mode QRIS otomatis tidak aktif (QRIS_MODE=' . config('services.qris.mode') . '). Pengecekan dilewati.');
            return self::SUCCESS;
        }

        $hours = (int) $this->option('hours');
        $this->info("Memeriksa tagihan QRIS belum lunas dalam {$hours} jam terakhir...");

        $unpaidSubscriptions = Subscription::with(['school', 'package'])
            ->where('status', 'unpaid')
            ->where(function ($q) {
                $q->where('payment_method', 'qris')
                  ->orWhereNull('payment_method');
            })
            ->where('created_at', '>=', now()->subHours($hours))
            ->get();

        if ($unpaidSubscriptions->isEmpty()) {
            $this->line('Tidak ada tagihan QRIS belum lunas yang perlu diverifikasi.');
            return self::SUCCESS;
        }

        $this->line("Ditemukan {$unpaidSubscriptions->count()} tagihan belum lunas. Menghubungi ShopeePay Gateway...");

        $paidCount = 0;
        foreach ($unpaidSubscriptions as $sub) {
            $schoolName = $sub->school?->name ?? 'Sekolah #' . $sub->school_id;
            $this->line("  -> Cek Tagihan #{$sub->id} | {$schoolName} | Rp " . number_format($sub->amount, 0, ',', '.'));

            try {
                $matched = ShopeePayService::checkSubscription($sub);
                if ($matched && !empty($matched['status']) && $matched['status'] === 'success') {
                    $ref = $matched['transactionId'] ?? $matched['orderSn'] ?? 'OK';
                    $this->info("     ✓ [LUNAS] Terverifikasi pembayaran ShopeePay (Ref: {$ref}). Langganan berhasil diaktifkan!");
                    $paidCount++;
                } else {
                    $this->comment("     - Belum ada mutasi masuk.");
                }
            } catch (\Throwable $e) {
                $this->error("     ✗ Error cek tagihan #{$sub->id}: " . $e->getMessage());
                Log::error("Error in qris:check-shopee for sub #{$sub->id}: " . $e->getMessage());
            }
        }

        $this->info("Selesai. {$paidCount} dari {$unpaidSubscriptions->count()} tagihan berhasil diverifikasi lunas.");
        return self::SUCCESS;
    }
}
