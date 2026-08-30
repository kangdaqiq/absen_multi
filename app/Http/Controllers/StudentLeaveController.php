<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentLeave;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Attendance;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentLeaveController extends Controller
{
    protected $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Tampilkan daftar pengajuan izin
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $query = StudentLeave::query()
            ->with(['student.kelas', 'approver'])
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });

        // Jika wali kelas biasa (bukan admin / super admin), batasi ke kelas yang diwalikan
        if ($user->role === 'wali_kelas' && $user->guru) {
            $waliKelasIds = Kelas::where('school_id', $schoolId)
                ->where(function ($q) use ($user) {
                    $q->where('wali_kelas_id', $user->guru->id)
                      ->orWhere('wali_kelas_2_id', $user->guru->id);
                })
                ->pluck('id');

            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', $waliKelasIds);
            });
        }

        // Hitung statistik ringkasan
        $statsQuery = clone $query;
        $totalPending  = (clone $statsQuery)->where('status', 'pending')->count();
        $totalApproved = (clone $statsQuery)->where('status', 'approved')->count();
        $totalRejected = (clone $statsQuery)->where('status', 'rejected')->count();
        $totalToday    = (clone $statsQuery)->whereDate('created_at', today())->count();

        // Filter Status Tab
        $statusTab = $request->input('status', 'pending');
        if (in_array($statusTab, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $statusTab);
        }

        // Filter Kelas
        if ($request->filled('kelas_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        // Filter Jenis
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        // Filter Rentang Tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->where('tanggal_mulai', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->where('tanggal_selesai', '<=', $request->tanggal_selesai);
        }

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('nama_pengaju', 'LIKE', "%{$search}%")
                  ->orWhere('no_wa_pengaju', 'LIKE', "%{$search}%")
                  ->orWhere('keterangan', 'LIKE', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('nama', 'LIKE', "%{$search}%")
                         ->orWhere('nis', 'LIKE', "%{$search}%");
                  });
            });
        }

        $leaves = $query->latest('id')->paginate(15)->withQueryString();

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get();

        return view('student-leaves.index', compact(
            'leaves',
            'kelasList',
            'statusTab',
            'totalPending',
            'totalApproved',
            'totalRejected',
            'totalToday'
        ));
    }

    /**
     * Setujui pengajuan izin & otomatis sinkronkan ke absensi
     */
    public function approve(Request $request, $id)
    {
        $user = auth()->user();
        $leave = StudentLeave::with('student')->findOrFail($id);

        if ($user->school_id && $leave->school_id != $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        DB::beginTransaction();
        try {
            $leave->update([
                'status'      => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Sinkronkan ke tabel attendance untuk setiap tanggal izin
            $period = CarbonPeriod::create($leave->tanggal_mulai, $leave->tanggal_selesai);
            $statusCode = ($leave->jenis === 'sakit') ? 'S' : 'I';
            $keterangan = "[Pengajuan " . ucfirst($leave->jenis) . "] " . $leave->keterangan;

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                
                Attendance::updateOrCreate(
                    [
                        'student_id' => $leave->student_id,
                        'tanggal'    => $dateStr,
                    ],
                    [
                        'status'          => $statusCode,
                        'keterangan'      => $keterangan,
                        'is_auto_alpha'   => false,
                        'updated_at'      => now(),
                    ]
                );
            }

            DB::commit();

            // Kirim notifikasi WA persetujuan ke Orang Tua / Siswa
            try {
                $this->wa->sendLeaveApprovedNotification($leave, $user->full_name);
            } catch (\Throwable $e) {
                Log::error("Failed to send WA Leave Approved: " . $e->getMessage());
            }

            return back()->with('success', "Pengajuan {$leave->code} an. {$leave->student->nama} BERHASIL DISETUJUI dan dicatat ke absensi.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error approving student leave: " . $e->getMessage());
            return back()->with('error', "Gagal memproses persetujuan: " . $e->getMessage());
        }
    }

    /**
     * Tolak pengajuan izin dengan alasan
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $leave = StudentLeave::with('student')->findOrFail($id);

        if ($user->school_id && $leave->school_id != $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        try {
            $leave->update([
                'status'          => 'rejected',
                'rejected_reason' => $request->rejected_reason,
                'approved_by'     => $user->id,
                'approved_at'     => now(),
            ]);

            // Kirim notifikasi WA penolakan ke Orang Tua / Siswa
            try {
                $this->wa->sendLeaveRejectedNotification($leave, $request->rejected_reason);
            } catch (\Throwable $e) {
                Log::error("Failed to send WA Leave Rejected: " . $e->getMessage());
            }

            return back()->with('success', "Pengajuan {$leave->code} an. {$leave->student->nama} telah DITOLAK.");
        } catch (\Throwable $e) {
            Log::error("Error rejecting student leave: " . $e->getMessage());
            return back()->with('error', "Gagal menolak pengajuan: " . $e->getMessage());
        }
    }

    /**
     * Input izin manual langsung dari dashboard
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $validated = $request->validate([
            'student_id'      => 'required|exists:siswa,id',
            'jenis'           => 'required|in:sakit,izin,dispensasi',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string|max:1000',
            'bukti_foto'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $student = Siswa::findOrFail($validated['student_id']);
        if ($schoolId && $student->school_id != $schoolId) {
            abort(403, 'Siswa bukan berasal dari sekolah Anda.');
        }

        $filePath = null;
        if ($request->hasFile('bukti_foto')) {
            $filePath = $request->file('bukti_foto')->store('surat_izin', 'public');
        }

        DB::beginTransaction();
        try {
            $code = StudentLeave::generateCode();

            $leave = StudentLeave::create([
                'school_id'       => $student->school_id,
                'student_id'      => $student->id,
                'code'            => $code,
                'jenis'           => $validated['jenis'],
                'tanggal_mulai'   => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'keterangan'      => $validated['keterangan'],
                'bukti_foto'      => $filePath,
                'pengaju'         => 'guru',
                'nama_pengaju'    => $user->full_name,
                'no_wa_pengaju'   => $student->wa_ortu ?? $student->no_wa,
                'status'          => 'approved',
                'approved_by'     => $user->id,
                'approved_at'     => now(),
            ]);

            // Sinkronkan ke attendance
            $period = CarbonPeriod::create($leave->tanggal_mulai, $leave->tanggal_selesai);
            $statusCode = ($leave->jenis === 'sakit') ? 'S' : 'I';
            $keterangan = "[Input Guru: " . $user->full_name . "] " . $leave->keterangan;

            foreach ($period as $date) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $leave->student_id,
                        'tanggal'    => $date->format('Y-m-d'),
                    ],
                    [
                        'status'        => $statusCode,
                        'keterangan'    => $keterangan,
                        'is_auto_alpha' => false,
                        'updated_at'    => now(),
                    ]
                );
            }

            DB::commit();

            return back()->with('success', "Izin siswa {$student->nama} berhasil dicatat dan disinkronkan ke absensi.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error manual student leave store: " . $e->getMessage());
            return back()->with('error', "Gagal mencatat izin: " . $e->getMessage());
        }
    }

    /**
     * Hapus data pengajuan
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $leave = StudentLeave::findOrFail($id);

        if ($user->school_id && $leave->school_id != $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($leave->bukti_foto && Storage::disk('public')->exists($leave->bukti_foto)) {
            Storage::disk('public')->delete($leave->bukti_foto);
        }

        $leave->delete();

        return back()->with('success', 'Data pengajuan izin berhasil dihapus.');
    }
}
