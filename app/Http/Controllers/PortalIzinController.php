<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentLeave;
use App\Models\Siswa;
use App\Models\School;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortalIzinController extends Controller
{
    protected $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Tampilkan form pengajuan izin mandiri
     */
    public function index(Request $request, $schoolCode = null)
    {
        $school = null;

        // 1. Cek dari parameter route
        if ($schoolCode) {
            $school = School::where('code', $schoolCode)->where('is_active', true)->first();
        }

        // 2. Cek dari custom domain
        if (!$school) {
            $host = $request->getHost();
            $globalHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
            if ($host !== $globalHost && $host !== 'localhost') {
                $school = School::where('domain', $host)->where('is_active', true)->first();
            }
        }

        // 3. Cek dari mode self-hosted
        if (!$school && config('app.mode') === 'self_hosted') {
            $school = School::where('is_active', true)->first();
        }

        // 4. Fallback jika SaaS & tanpa kode: ambil daftar sekolah aktif
        $schools = null;
        if (!$school) {
            $schools = School::where('is_active', true)->orderBy('name')->get();
        }

        return view('portal-izin.index', compact('school', 'schools'));
    }

    /**
     * Pencarian autocomplete data siswa
     */
    public function searchStudent(Request $request)
    {
        $schoolId = $request->input('school_id');
        $query = trim($request->input('q', ''));

        if (!$schoolId || strlen($query) < 2) {
            return response()->json([]);
        }

        $students = Siswa::where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('nama', 'LIKE', "%{$query}%")
                  ->orWhere('nisn', 'LIKE', "%{$query}%")
                  ->orWhere('nis', 'LIKE', "%{$query}%");
            })
            ->with('kelas')
            ->limit(10)
            ->get(['id', 'nama', 'nisn', 'nis', 'kelas_id', 'no_wa', 'wa_ortu', 'nama_ortu']);

        $results = $students->map(function ($s) {
            return [
                'id'         => $s->id,
                'nama'       => $s->nama,
                'nisn'       => $s->nisn,
                'nis'        => $s->nis,
                'kelas'      => $s->kelas ? $s->kelas->nama_kelas : '-',
                'no_wa'      => $s->no_wa,
                'wa_ortu'    => $s->wa_ortu,
                'nama_ortu'  => $s->nama_ortu,
            ];
        });

        return response()->json($results);
    }

    /**
     * Simpan pengajuan izin/sakit mandiri
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id'       => 'required|exists:schools,id',
            'student_id'      => 'required|exists:siswa,id',
            'jenis'           => 'required|in:sakit,izin,dispensasi',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string|max:1000',
            'bukti_foto'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'pengaju'         => 'required|in:ortu,siswa,guru',
            'nama_pengaju'    => 'required|string|max:100',
            'no_wa_pengaju'   => 'required|string|max:25',
        ]);

        $filePath = null;
        if ($request->hasFile('bukti_foto')) {
            $filePath = $request->file('bukti_foto')->store('surat_izin', 'public');
        }

        $code = StudentLeave::generateCode();

        $leave = StudentLeave::create([
            'school_id'       => $validated['school_id'],
            'student_id'      => $validated['student_id'],
            'code'            => $code,
            'jenis'           => $validated['jenis'],
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'keterangan'      => $validated['keterangan'],
            'bukti_foto'      => $filePath,
            'pengaju'         => $validated['pengaju'],
            'nama_pengaju'    => $validated['nama_pengaju'],
            'no_wa_pengaju'   => $validated['no_wa_pengaju'],
            'status'          => 'pending',
        ]);

        // Kirim notifikasi WA ke Wali Kelas
        try {
            $this->wa->sendLeaveSubmittedNotification($leave);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send WA Leave Submission: " . $e->getMessage());
        }

        return redirect()->route('portal-izin.status', $code)->with('success', 'Pengajuan ' . ucfirst($leave->jenis) . ' berhasil dikirim! Silakan simpan nomor resi Anda.');
    }

    /**
     * Tampilkan status pelacakan pengajuan izin
     */
    public function status($code)
    {
        $leave = StudentLeave::where('code', $code)
            ->with(['student.kelas', 'school', 'approver'])
            ->firstOrFail();

        return view('portal-izin.status', compact('leave'));
    }
}
