<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalPelajaran;
use App\Models\Setting;
use App\Models\MessageQueue;
use Carbon\Carbon;

class SendTeacherScheduleCommand extends Command
{
    protected $signature = 'schedule:send-teacher-schedule';
    protected $description = 'Send daily teacher schedule to WhatsApp group at 06:00';

    public function handle()
    {
        $targetJid = Setting::where('setting_key', 'report_target_jid')->value('setting_value');
        
        if (!$targetJid) {
            $this->error("Report target JID not set in settings.");
            return 1;
        }

        $today = Carbon::now();
        $hari = $today->locale('id')->dayName;
        $tanggal = $today->format('d/m/Y');
        
        $this->info("Sending teacher schedule for: {$hari}, {$tanggal}");
        
        // Get today's schedule
        $schedules = JadwalPelajaran::where('hari', $hari)
            ->with(['guru', 'mapel', 'kelas'])
            ->orderBy('jam_mulai')
            ->get();
        
        if ($schedules->isEmpty()) {
            $this->info("No teacher schedule for today ({$hari})");
            return 0;
        }
        
        // Group by teacher
        $teacherSchedules = $schedules->groupBy('guru_id');
        
        // Build message
        $message = "📅 *JADWAL MENGAJAR HARI INI*\n";
        $message .= "_{$hari}, {$tanggal}_\n";
        $message .= str_repeat("─", 30) . "\n\n";
        
        $teacherCount = 0;
        foreach ($teacherSchedules as $guruId => $lessons) {
            $guru = $lessons->first()->guru;
            if (!$guru) continue;
            
            $teacherCount++;
            $message .= "*{$teacherCount}. {$guru->nama}*\n";
            
            foreach ($lessons as $lesson) {
                $jamMulai = substr($lesson->jam_mulai, 0, 5);
                $jamSelesai = substr($lesson->jam_selesai, 0, 5);
                $message .= "   • {$jamMulai}-{$jamSelesai} | {$lesson->mapel->nama_mapel} | {$lesson->kelas->nama_kelas}\n";
            }
            $message .= "\n";
        }
        
        $message .= str_repeat("─", 30) . "\n";
        $message .= "Total: *{$teacherCount} Guru* mengajar hari ini\n";
        $message .= "\n_Semangat mengajar! 💪_";
        
        // Queue message to single target (teacher schedule only goes to one group)
        MessageQueue::create([
            'phone_number' => $targetJid,
            'message' => $message,
            'status' => 'pending',
            'created_at' => now()
        ]);
        
        $this->info("✓ Teacher schedule queued ({$teacherCount} teachers)");
        return 0;
    }
}
