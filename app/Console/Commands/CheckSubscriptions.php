<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\MessageQueue;
use App\Services\SubscriptionNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check school subscriptions and send reminders or deactivate if expired.';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionNotificationService $notificationService)
    {
        $this->info('Checking subscriptions...');

        // 1. Check for expirations today (or past due)
        $expiredSchools = School::where('is_active', true)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', Carbon::now())
            ->get();

        foreach ($expiredSchools as $school) {
            $this->info("School {$school->name} has expired. Deactivating premium features.");
            
            $school->update([
                'wa_enabled' => false,
                'bot_enabled' => false,
            ]);

            // Notify admin with direct payment link
            $result = $notificationService->sendRenewalInvoiceWhatsApp($school, daysRemaining: -1);
            $this->line("  -> Result: " . ($result['message'] ?? 'Done'));
        }

        // 2. Check for upcoming expirations (7 days, 3 days, 1 day, 0 day)
        $daysToCheck = [7, 3, 1, 0];

        foreach ($daysToCheck as $days) {
            $startDate = Carbon::now()->addDays($days)->startOfDay();
            $endDate = Carbon::now()->addDays($days)->endOfDay();

            $upcomingSchools = School::where('is_active', true)
                ->whereBetween('expired_at', [$startDate, $endDate])
                ->get();

            foreach ($upcomingSchools as $school) {
                $this->info("Sending {$days}-day reminder & invoice to {$school->name}");
                $result = $notificationService->sendRenewalInvoiceWhatsApp($school, daysRemaining: $days);
                $this->line("  -> Result: " . ($result['message'] ?? 'Done'));
            }
        }

        $this->info('Subscription check completed.');
    }
}
