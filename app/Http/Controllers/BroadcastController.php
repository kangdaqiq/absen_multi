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
            'target_class_id' => 'required',
            'message' => 'required|string|min:5',
        ]);

        $targetClassId = $request->target_class_id;
        $messageBody = $request->message;
        $students = [];

        if ($targetClassId === 'all') {
            // Get all active students
            $students = Siswa::whereHas('kelas', function ($q) {
                // Optional: Check if class is active? 
                // Usually for broadcast we might want to send to everyone regardless of attendance status?
                // Let's assume sending to all registered students.
            })->whereNotNull('no_wa')->get();
        } else {
            // Get students in specific class
            $students = Siswa::where('kelas_id', $targetClassId)
                ->whereNotNull('no_wa')
                ->get();
        }

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada siswa dengan nomor WhatsApp di target yang dipilih.');
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
