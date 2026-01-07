<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\MessageQueue;
use Illuminate\Support\Facades\DB;

class BroadcastController extends Controller
{
    /**
     * Display the broadcast form.
     */
    public function index()
    {
        $kelas = Kelas::orderBy('nama_kelas')->get();
        return view('broadcast.index', compact('kelas'));
    }

    /**
     * Send the broadcast message.
     */
    public function send(Request $request)
    {
        $request->validate([
            'target_class_ids' => 'required|array|min:1',
            'target_class_ids.*' => 'exists:kelas,id',
            'message' => 'required|string|min:5',
        ]);

        $targetClassIds = $request->target_class_ids;
        $messageBody = $request->message;

        // Get students from selected classes
        $students = Siswa::whereIn('kelas_id', $targetClassIds)
            ->whereNotNull('no_wa')
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada siswa dengan nomor WhatsApp di kelas yang dipilih.');
        }

        $count = 0;
        $now = now();

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Ensure number is valid-ish
                if (strlen($student->no_wa) < 9)
                    continue;

                $phone = $this->formatWhatsApp($student->no_wa);

                // Personalized message?
                // Maybe redundant to personalize for generic broadcast, but helpful.
                // Let's keep it simple generic message, or append header.

                $finalMessage = "📢 *PENGUMUMAN SEKOLAH*\n";
                $finalMessage .= "Kepada: " . $student->nama . "\n\n";
                $finalMessage .= $messageBody . "\n\n";
                $finalMessage .= "_Dikirim oleh Admin_";

                MessageQueue::create([
                    'phone_number' => $phone,
                    'message' => $finalMessage,
                    'status' => 'pending',
                    'created_at' => $now,
                ]);
                $count++;
            }
            DB::commit();
            return back()->with('success', "Broadcast berhasil dijadwalkan untuk {$count} siswa.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function formatWhatsApp($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        return $phone . '@s.whatsapp.net';
    }
}
