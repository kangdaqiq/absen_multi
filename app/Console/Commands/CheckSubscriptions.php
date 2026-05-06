<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\MessageQueue;
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
    public function handle()
    {
        $this->info('Checking subscriptions...');

        // 1. Check for expirations today (or past due)
        $expiredSchools = School::where('is_active', true)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', Carbon::now())
            ->get();

        foreach ($expiredSchools as $school) {
            $this->info("School {$school->name} has expired. Deactivating features.");
            
            // We can either set is_active to false, or just disable wa and bot features
            // Let's just disable premium features for now
            $school->update([
                'wa_enabled' => false,
                'bot_enabled' => false,
            ]);

            // Notify the operator
            $this->sendReminder($school, 'Masa aktif langganan sistem absensi Anda telah kedaluwarsa. Fitur WhatsApp dan Bot telah dinonaktifkan. Segera lakukan perpanjangan.');
        }

        // 2. Check for upcoming expirations (7 days, 3 days, 1 day)
        $daysToCheck = [7, 3, 1];

        foreach ($daysToCheck as $days) {
            $startDate = Carbon::now()->addDays($days)->startOfDay();
            $endDate = Carbon::now()->addDays($days)->endOfDay();

            $upcomingSchools = School::where('is_active', true)
                ->whereBetween('expired_at', [$startDate, $endDate])
                ->get();

            foreach ($upcomingSchools as $school) {
                $this->info("Sending {$days}-day reminder to {$school->name}");
                $this->sendReminder($school, "Langganan sistem absensi Anda akan kedaluwarsa dalam {$days} hari (" . $school->expired_at->format('d M Y') . "). Segera lakukan perpanjangan agar layanan tidak terhenti.");
            }
        }

        $this->info('Subscription check completed.');
    }

    private function sendReminder(School $school, string $message)
    {
        if (empty($school->operator_phone)) {
            Log::warning("Cannot send reminder to {$school->name}: No operator phone number.");
            return;
        }

        // Add to WA Message Queue
        MessageQueue::create([
            'school_id' => $school->id,
            'phone_number' => $school->operator_phone,
            'message' => "Halo Admin {$school->name},\n\n{$message}\n\nTerima kasih.",
            'status' => 'pending',
        ]);
    }
}
