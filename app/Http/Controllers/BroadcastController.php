<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\MessageQueue;
use App\Models\School;
use App\Models\Setting;
use App\Jobs\SendTelegramMessageJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    /**
     * Display the broadcast form.
     */
    public function index()
    {
        $user = auth()->user();
        $kelasQuery = Kelas::orderBy('nama_kelas');

        // Filter by school_id for non-super admin users
        if ($user && !$user->isSuperAdmin()) {
            $kelasQuery->where('school_id', $user->school_id);
        }

        $kelas = $kelasQuery->get();

        return view('broadcast.index', compact('kelas'));
    }

    /**
     * Send the broadcast message.
     */
    public function send(Request $request)
    {
        $request->validate([
            'target_class_ids'   => 'required|array|min:1',
            'target_class_ids.*' => 'exists:kelas,id',
            'channel'            => 'required|in:wa,tele,both',
            'target_recipient'   => 'required|in:siswa,ortu,both',
            'message'            => 'required|string|min:3',
        ]);

        $user            = auth()->user();
        $targetClassIds  = $request->target_class_ids;
        $channel         = $request->channel;
        $targetRecipient = $request->target_recipient;
        $rawMessage      = $request->message;

        // Rate limit per hour per school
        $rateLimitPerHour = (int) env('WA_RATE_LIMIT_PER_HOUR', 500);

        // Default Last Seen threshold (from school/system setting or fallback 3 days / 72 hours)
        $userSchoolId = $user ? $user->school_id : null;
        $lastSeenDays = (int) (Setting::where('school_id', $userSchoolId)->where('setting_key', 'last_seen_expiry_days')->value('setting_value') 
            ?: Setting::where('school_id', 0)->where('setting_key', 'last_seen_expiry_days')->value('setting_value') 
            ?: 3);
        $lastSeenHours = $lastSeenDays * 24;

        // Query students with eager loaded relationships
        $studentsQuery = Siswa::with(['kelas', 'school'])
            ->whereIn('kelas_id', $targetClassIds);

        if ($user && !$user->isSuperAdmin()) {
            $studentsQuery->where('school_id', $user->school_id);
        }

        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan data siswa pada kelas yang dipilih.');
        }

        $countWaQueued   = 0;
        $countTeleSent   = 0;
        $countSkipped    = 0;
        $now             = now();
        $schoolCache     = [];

        // Scheduling pointer for WhatsApp with random initial delay 1-15 minutes (60 - 900 seconds)
        $initialDelaySeconds = rand(60, 900);
        $scheduledPointer    = $now->copy()->addSeconds($initialDelaySeconds);
        $hourlyCounter       = []; // Track messages scheduled per school per hour block

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                $schoolId   = $student->school_id ?: $userSchoolId;
                $school     = $student->school ?: ($schoolId ? ($schoolCache[$schoolId] ??= School::find($schoolId)) : null);
                $schoolName = $school ? $school->name : 'Sekolah';
                $className  = $student->kelas ? $student->kelas->nama_kelas : '-';

                // Determine recipients based on target_recipient ('siswa', 'ortu', 'both')
                $recipients = [];

                if (in_array($targetRecipient, ['siswa', 'both'])) {
                    $recipients[] = [
                        'type'            => 'siswa',
                        'label'           => 'Siswa',
                        'name'            => $student->nama,
                        'phone'           => $student->no_wa,
                        'telegram_chat_id'=> $student->telegram_chat_id,
                        'last_seen'       => $student->last_seen_siswa,
                        'is_active'       => $student->isSiswaWithinLastSeen($lastSeenHours),
                    ];
                }

                if (in_array($targetRecipient, ['ortu', 'both'])) {
                    $recipients[] = [
                        'type'            => 'ortu',
                        'label'           => 'Orang Tua / Wali',
                        'name'            => 'Orang Tua / Wali dari ' . $student->nama,
                        'phone'           => $student->wa_ortu,
                        'telegram_chat_id'=> $student->telegram_ortu_chat_id,
                        'last_seen'       => $student->last_seen_ortu,
                        'is_active'       => $student->isOrtuWithinLastSeen($lastSeenHours),
                    ];
                }

                foreach ($recipients as $recipient) {
                    // Check Last Seen filter by default rule
                    if (!$recipient['is_active']) {
                        $countSkipped++;
                        continue;
                    }

                    // Build Personalized Message
                    $replacements = [
                        '{nama}'    => $student->nama,
                        '{penerima}'=> $recipient['label'],
                        '{kelas}'   => $className,
                        '{nis}'     => $student->nis ?? '-',
                        '{alamat}'  => $student->alamat ?? '-',
                        '{sekolah}' => $schoolName,
                    ];
                    $personalizedMsg = str_replace(array_keys($replacements), array_values($replacements), $rawMessage);

                    // 1. Process WhatsApp
                    if (in_array($channel, ['wa', 'both'])) {
                        $phone = $this->formatWhatsApp($recipient['phone']);
                        if ($phone && strlen($phone) >= 9) {
                            $waMessage = "📢 *PENGUMUMAN SEKOLAH*\n";
                            $waMessage .= "Kepada: *" . $recipient['name'] . "*\n";
                            $waMessage .= "Kelas: " . $className . "\n\n";
                            $waMessage .= $personalizedMsg . "\n\n";
                            $waMessage .= "_Dikirim otomatis oleh Sistem_";

                            // Check and enforce rate limit per hour
                            $hourKey = ($schoolId ?? 0) . '_' . $scheduledPointer->format('Y-m-d_H');
                            if (!isset($hourlyCounter[$hourKey])) {
                                // Count already queued/sent messages for this school in this hour slot
                                $existingCount = MessageQueue::where('school_id', $schoolId)
                                    ->whereBetween('scheduled_at', [
                                        $scheduledPointer->copy()->startOfHour(),
                                        $scheduledPointer->copy()->endOfHour()
                                    ])
                                    ->count();
                                $hourlyCounter[$hourKey] = $existingCount;
                            }

                            // If this hour's quota is reached, advance scheduledPointer to the next hour
                            if ($rateLimitPerHour > 0 && $hourlyCounter[$hourKey] >= $rateLimitPerHour) {
                                $scheduledPointer = $scheduledPointer->copy()->startOfHour()->addHour()->addMinutes(rand(1, 5));
                                $hourKey = ($schoolId ?? 0) . '_' . $scheduledPointer->format('Y-m-d_H');
                                $hourlyCounter[$hourKey] = 0;
                            }

                            $scheduledAt = $scheduledPointer->copy();
                            $hourlyCounter[$hourKey]++;

                            // Add random jitter delay (8 - 15 seconds) for the next message
                            $jitter = rand(8, 15);
                            $scheduledPointer->addSeconds($jitter);

                            $mq = new MessageQueue([
                                'school_id'    => $schoolId,
                                'phone_number' => $phone,
                                'message'      => $waMessage,
                                'status'       => 'pending',
                                'priority'     => 0,
                                'scheduled_at' => $scheduledAt,
                                'created_at'   => $now,
                            ]);

                            $mq->save();
                            $countWaQueued++;
                        }
                    }

                    // 2. Process Telegram
                    if (in_array($channel, ['tele', 'both'])) {
                        $chatId = $recipient['telegram_chat_id'];
                        if ($chatId && $school && $school->telegram_enabled && $school->telegram_bot_token) {
                            $teleMessage = "📢 <b>PENGUMUMAN SEKOLAH</b>\n";
                            $teleMessage .= "Kepada: <b>" . htmlspecialchars($recipient['name']) . "</b>\n";
                            $teleMessage .= "Kelas: " . htmlspecialchars($className) . "\n\n";

                            // Convert markdown-style tags to HTML
                            $formattedBody = htmlspecialchars($personalizedMsg);
                            $formattedBody = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $formattedBody);
                            $formattedBody = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $formattedBody);

                            $teleMessage .= $formattedBody . "\n\n";
                            $teleMessage .= "<i>Dikirim otomatis oleh Sistem</i>";

                            if (!empty($schoolName) && !str_contains($teleMessage, "<b>" . trim($schoolName) . "</b>")) {
                                $teleMessage .= "\n\n<b>" . htmlspecialchars(trim($schoolName)) . "</b>";
                            }

                            SendTelegramMessageJob::dispatch(
                                $school->telegram_bot_token,
                                (string) $chatId,
                                $teleMessage,
                                $schoolId
                            );
                            $countTeleSent++;
                        }
                    }
                }
            }

            DB::commit();

            if ($countWaQueued === 0 && $countTeleSent === 0) {
                $msg = 'Tidak ada kontak penerima yang memenuhi kriteria pengiriman.';
                if ($countSkipped > 0) {
                    $msg .= " ({$countSkipped} kontak dilewati karena tidak aktif dalam filter Last Seen {$lastSeenDays} hari terakhir).";
                }
                return back()->with('error', $msg);
            }

            $successDetails = [];
            if ($countWaQueued > 0) {
                $startDelayMinutes = round($initialDelaySeconds / 60, 1);
                $successDetails[] = "{$countWaQueued} pesan WhatsApp berhasil dijadwalkan (Mulai kirim: ~{$startDelayMinutes} mnt lagi, jeda dinamis 8-15s, rate limit maks {$rateLimitPerHour}/jam)";
            }
            if ($countTeleSent > 0) {
                $successDetails[] = "{$countTeleSent} pesan Telegram berhasil dikirim ke antrean pengiriman";
            }
            if ($countSkipped > 0) {
                $successDetails[] = "{$countSkipped} kontak dilewati (tidak aktif dalam filter Last Seen {$lastSeenDays} hari terakhir)";
            }

            return back()->with('success', "Broadcast Berhasil Diproses!\n• " . implode("\n• ", $successDetails));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Broadcast Send Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan saat memproses broadcast: ' . $e->getMessage());
        }
    }

    /**
     * Helper to standardize WhatsApp phone number.
     */
    private function formatWhatsApp(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
