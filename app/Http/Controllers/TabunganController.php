<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\School;
use App\Models\Siswa;
use App\Models\TabunganSiswa;
use App\Services\FcmNotificationService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TabunganController extends Controller
{
    /**
     * Layar Khusus Loket Kasir Tabungan (POS Fullscreen Realtime)
     */
    public function kasir(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403, 'Akses ditolak. Layar POS Kasir hanya untuk Admin / Petugas Loket Tabungan.');
        }

        $schoolId = $user->school_id;
        $school = $schoolId ? School::find($schoolId) : null;

        $statsBase = TabunganSiswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId));
        $totalSetorToday = (int) (clone $statsBase)->where('jenis', 'setor')->whereDate('tanggal', today())->sum('nominal');
        $totalTarikToday = (int) (clone $statsBase)->where('jenis', 'tarik')->whereDate('tanggal', today())->sum('nominal');
        $totalTrxToday   = (clone $statsBase)->whereDate('tanggal', today())->count();

        $saldoSiswaQuery = Siswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId));
        $totalSaldoAktif = (int) (clone $saldoSiswaQuery)->sum('saldo_tabungan');
        $jumlahPenabung  = (clone $saldoSiswaQuery)->where('saldo_tabungan', '>', 0)->count();

        // 20 Transaksi Terakhir Hari Ini
        $todayTransactions = TabunganSiswa::with(['student.kelas', 'recorder'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->whereDate('tanggal', today())
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($t) => [
                'id'                      => $t->id,
                'code'                    => $t->code,
                'tanggal'                 => $t->tanggal ? $t->tanggal->format('d/m/Y') : '-',
                'waktu'                   => $t->waktu ?: '-',
                'jenis'                   => $t->jenis,
                'jenis_label'             => $t->jenis_label,
                'nominal'                 => (int) $t->nominal,
                'nominal_formatted'       => $t->nominal_formatted,
                'saldo_sesudah'           => (int) $t->saldo_sesudah,
                'saldo_sesudah_formatted' => $t->saldo_sesudah_formatted,
                'metode'                  => $t->metode,
                'keterangan'              => $t->keterangan,
                'student_id'              => $t->student_id,
                'student_nama'            => $t->student?->nama ?? '-',
                'student_nis'             => $t->student?->nis ?? '-',
                'student_kelas'           => $t->student?->kelas?->nama_kelas ?? '-',
                'recorder_nama'           => $t->recorder?->full_name ?? 'Admin',
            ])->values();

        return view('tabungan.kasir', compact(
            'school',
            'user',
            'totalSetorToday',
            'totalTarikToday',
            'totalTrxToday',
            'totalSaldoAktif',
            'jumlahPenabung',
            'todayTransactions'
        ));
    }

    /**
     * Halaman Utama Mutasi & Loket Transaksi Tabungan Siswa
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $canManage = $user->isAdmin();

        $isWaliKelas = !$canManage && $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $query = TabunganSiswa::with(['student.kelas', 'recorder'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        // Statistik keseluruhan (sebelum filter tabel, namun tetap mengikuti scope sekolah/wali kelas)
        $statsBase = clone $query;
        $totalSetorAll = (int) (clone $statsBase)->where('jenis', 'setor')->sum('nominal');
        $totalTarikAll = (int) (clone $statsBase)->where('jenis', 'tarik')->sum('nominal');
        $totalSetorToday = (int) (clone $statsBase)->where('jenis', 'setor')->whereDate('tanggal', today())->sum('nominal');
        $totalTarikToday = (int) (clone $statsBase)->where('jenis', 'tarik')->whereDate('tanggal', today())->sum('nominal');
        $totalTrxToday = (clone $statsBase)->whereDate('tanggal', today())->count();

        $saldoSiswaQuery = Siswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));
        $totalSaldoAktif = (int) (clone $saldoSiswaQuery)->sum('saldo_tabungan');
        $jumlahPenabung = (clone $saldoSiswaQuery)->where('saldo_tabungan', '>', 0)->count();

        // Filter Tabel Mutasi
        if ($request->filled('jenis') && in_array($request->jenis, ['setor', 'tarik'])) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('metode') && in_array($request->metode, ['tunai', 'transfer', 'qris'])) {
            $query->where('metode', $request->metode);
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        $selectedStudent = null;
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
            $selectedStudent = Siswa::with('kelas')->find($request->student_id);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan', 'LIKE', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nama', 'LIKE', "%{$search}%")
                            ->orWhere('nis', 'LIKE', "%{$search}%")
                            ->orWhere('uid_rfid', 'LIKE', "%{$search}%");
                    });
            });
        }

        $tabungans = $query->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->orderBy('nama_kelas')
            ->get();

        $siswaList = Siswa::with('kelas:id,nama_kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nis', 'uid_rfid', 'kelas_id', 'saldo_tabungan', 'foto', 'school_id']);

        $selectedStudentPayload = $selectedStudent ? $this->formatStudentLookupPayload($selectedStudent) : null;

        return view('tabungan.index', compact(
            'tabungans',
            'kelasList',
            'siswaList',
            'selectedStudent',
            'selectedStudentPayload',
            'canManage',
            'totalSaldoAktif',
            'jumlahPenabung',
            'totalSetorAll',
            'totalTarikAll',
            'totalSetorToday',
            'totalTarikToday',
            'totalTrxToday'
        ));
    }

    /**
     * Endpoint AJAX Lookup Siswa via Scan RFID USB / NIS / Nama untuk Loket Cepat
     */
    public function scanLookup(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $keyword = trim((string) $request->query('q', ''));

        if ($keyword === '') {
            return response()->json([
                'success' => false,
                'message' => 'Masukkan UID RFID, NIS, atau Nama Siswa.',
            ], 422);
        }

        $baseQuery = Siswa::with('kelas:id,nama_kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        // 1. Prioritaskan exact match pada UID RFID atau NIS (untuk scanner RFID/barcode)
        $exactStudent = (clone $baseQuery)->where(function ($q) use ($keyword) {
            $q->where('uid_rfid', $keyword)
                ->orWhere('nis', $keyword)
                ->orWhere('nisn', $keyword);
        })->first();

        if ($exactStudent) {
            return response()->json([
                'success'    => true,
                'exact'      => true,
                'student'    => $this->formatStudentLookupPayload($exactStudent),
                'candidates' => [$this->formatStudentLookupPayload($exactStudent)],
            ]);
        }

        // 2. Fallback pencarian parsial berdasarkan nama / NIS
        $candidates = (clone $baseQuery)->where(function ($q) use ($keyword) {
            $q->where('nama', 'LIKE', "%{$keyword}%")
                ->orWhere('nis', 'LIKE', "%{$keyword}%")
                ->orWhere('uid_rfid', 'LIKE', "%{$keyword}%");
        })
            ->orderBy('nama')
            ->limit(10)
            ->get();

        if ($candidates->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Siswa dengan kata kunci atau RFID '{$keyword}' tidak ditemukan.",
            ], 404);
        }

        $formattedCandidates = $candidates->map(fn($s) => $this->formatStudentLookupPayload($s))->values();

        return response()->json([
            'success'    => true,
            'exact'      => $candidates->count() === 1,
            'student'    => $formattedCandidates->first(),
            'candidates' => $formattedCandidates,
        ]);
    }

    private function formatStudentLookupPayload(Siswa $student): array
    {
        $recent = TabunganSiswa::where('student_id', $student->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id'            => $t->id,
                'code'          => $t->code,
                'tanggal'       => $t->tanggal ? $t->tanggal->format('d/m/Y') : '-',
                'waktu'         => $t->waktu ?: '-',
                'jenis'         => $t->jenis,
                'jenis_label'   => $t->jenis_label,
                'nominal'       => (int) $t->nominal,
                'nominal_str'   => $t->nominal_formatted,
                'saldo_sesudah' => (int) $t->saldo_sesudah,
                'saldo_str'     => $t->saldo_sesudah_formatted,
                'keterangan'    => $t->keterangan,
            ]);

        return [
            'id'              => $student->id,
            'nama'            => $student->nama,
            'nis'             => $student->nis ?? '-',
            'uid_rfid'        => $student->uid_rfid ?? '-',
            'kelas_id'        => $student->kelas_id,
            'kelas_nama'      => $student->kelas?->nama_kelas ?? '-',
            'saldo_tabungan'  => (int) $student->saldo_tabungan,
            'saldo_formatted' => 'Rp ' . number_format((int) $student->saldo_tabungan, 0, ',', '.'),
            'foto_url'        => $student->foto_url,
            'recent'          => $recent,
        ];
    }

    /**
     * Simpan Transaksi Setor / Tarik Tabungan (Hanya Admin / Super Admin)
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403, 'Hanya Admin / Petugas Tabungan yang berhak mencatat transaksi tabungan.');
        }

        // Bersihkan titik/koma pada input nominal jika dikirim dalam format ribuan (misal "50.000")
        if ($request->has('nominal')) {
            $cleanNominal = preg_replace('/[^0-9]/', '', (string) $request->input('nominal'));
            $request->merge(['nominal' => $cleanNominal !== '' ? (int) $cleanNominal : 0]);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:siswa,id',
            'jenis'      => 'required|in:setor,tarik',
            'nominal'    => 'required|integer|min:500|max:100000000',
            'tanggal'    => 'required|date',
            'waktu'      => 'nullable|string|max:10',
            'metode'     => 'nullable|in:tunai,transfer,qris',
            'keterangan' => 'nullable|string|max:500',
        ], [
            'student_id.required' => 'Pilih siswa terlebih dahulu.',
            'nominal.min'         => 'Nominal transaksi minimal Rp 500.',
        ]);

        $schoolId = $user->school_id;

        try {
            $tabungan = DB::transaction(function () use ($validated, $user, $schoolId) {
                $student = Siswa::with('kelas')->lockForUpdate()->findOrFail($validated['student_id']);

                if ($schoolId && (int) $student->school_id !== (int) $schoolId) {
                    abort(403, 'Siswa bukan berasal dari sekolah Anda.');
                }

                $currentSaldo = (int) $student->saldo_tabungan;
                $nominal = (int) $validated['nominal'];
                $jenis = $validated['jenis'];

                if ($jenis === 'tarik' && $nominal > $currentSaldo) {
                    $saldoStr = 'Rp ' . number_format($currentSaldo, 0, ',', '.');
                    throw new \RuntimeException("Saldo tabungan {$student->nama} tidak mencukupi untuk penarikan (Saldo tersedia: {$saldoStr}).");
                }

                $newSaldo = $jenis === 'setor' ? ($currentSaldo + $nominal) : ($currentSaldo - $nominal);

                $trx = TabunganSiswa::create([
                    'school_id'     => $student->school_id,
                    'student_id'    => $student->id,
                    'recorded_by'   => $user->id,
                    'code'          => TabunganSiswa::generateCode(),
                    'tanggal'       => Carbon::parse($validated['tanggal'])->format('Y-m-d'),
                    'waktu'         => !empty($validated['waktu']) ? $validated['waktu'] : now()->format('H:i'),
                    'jenis'         => $jenis,
                    'nominal'       => $nominal,
                    'saldo_sebelum' => $currentSaldo,
                    'saldo_sesudah' => $newSaldo,
                    'metode'        => $validated['metode'] ?? 'tunai',
                    'keterangan'    => !empty($validated['keterangan']) ? trim($validated['keterangan']) : null,
                ]);

                // Rekalkulasi penuh untuk menjaga urutan tanggal kronologis & update siswa.saldo_tabungan
                $finalBalance = TabunganSiswa::recalculateStudentBalance($student->id);
                if ($finalBalance < 0) {
                    throw new \RuntimeException('Transaksi menyebabkan saldo minus pada urutan tanggal tersebut.');
                }

                return $trx->fresh(['student.kelas', 'recorder']);
            });
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['nominal' => $e->getMessage()]);
        }

        // Kirim Notifikasi WhatsApp ke Orang Tua & Siswa
        try {
            app(WhatsAppService::class)->sendTabunganNotification($tabungan, $user->full_name ?? 'Admin');
        } catch (\Throwable $e) {
            Log::warning('Failed to queue WA tabungan notification: ' . $e->getMessage());
        }

        // Kirim Push Notification FCM ke aplikasi Android/PWA Siswa
        try {
            if ($tabungan->student) {
                $title = $tabungan->jenis === 'setor'
                    ? "Setoran Tabungan Masuk (+{$tabungan->nominal_formatted})"
                    : "Penarikan Tabungan (-{$tabungan->nominal_formatted})";
                $body = "Saldo tabungan Anda saat ini: {$tabungan->saldo_sesudah_formatted}. " . ($tabungan->keterangan ?: '');

                FcmNotificationService::sendToSiswa(
                    $tabungan->student,
                    $title,
                    trim($body),
                    [
                        'type'        => 'student_tabungan_recorded',
                        'tabungan_id' => (string) $tabungan->id,
                        'code'        => $tabungan->code,
                        'jenis'       => $tabungan->jenis,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('FCM tabungan notification failed: ' . $e->getMessage());
        }

        $msg = "Transaksi {$tabungan->jenis_label} ({$tabungan->nominal_formatted}) an. {$tabungan->student->nama} berhasil disimpan. Saldo akhir: {$tabungan->saldo_sesudah_formatted}.";

        if ($request->expectsJson()) {
            $statsBase = TabunganSiswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId));
            $totalSetorToday = (int) (clone $statsBase)->where('jenis', 'setor')->whereDate('tanggal', today())->sum('nominal');
            $totalTarikToday = (int) (clone $statsBase)->where('jenis', 'tarik')->whereDate('tanggal', today())->sum('nominal');
            $totalTrxToday   = (clone $statsBase)->whereDate('tanggal', today())->count();

            return response()->json([
                'success'  => true,
                'message'  => $msg,
                'tabungan' => [
                    'id'                      => $tabungan->id,
                    'code'                    => $tabungan->code,
                    'tanggal'                 => $tabungan->tanggal ? $tabungan->tanggal->format('d/m/Y') : '-',
                    'waktu'                   => $tabungan->waktu ?: '-',
                    'jenis'                   => $tabungan->jenis,
                    'jenis_label'             => $tabungan->jenis_label,
                    'nominal'                 => (int) $tabungan->nominal,
                    'nominal_formatted'       => $tabungan->nominal_formatted,
                    'saldo_sesudah'           => (int) $tabungan->saldo_sesudah,
                    'saldo_sesudah_formatted' => $tabungan->saldo_sesudah_formatted,
                    'metode'                  => $tabungan->metode,
                    'keterangan'              => $tabungan->keterangan,
                    'student_id'              => $tabungan->student_id,
                    'student_nama'            => $tabungan->student?->nama ?? '-',
                    'student_nis'             => $tabungan->student?->nis ?? '-',
                    'student_kelas'           => $tabungan->student?->kelas?->nama_kelas ?? '-',
                    'recorder_nama'           => $user->full_name ?? 'Admin',
                ],
                'student'  => $this->formatStudentLookupPayload($tabungan->student->fresh(['kelas'])),
                'stats_today' => [
                    'setor'           => $totalSetorToday,
                    'setor_formatted' => 'Rp ' . number_format($totalSetorToday, 0, ',', '.'),
                    'tarik'           => $totalTarikToday,
                    'tarik_formatted' => 'Rp ' . number_format($totalTarikToday, 0, ',', '.'),
                    'trx_count'       => $totalTrxToday,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Hapus Transaksi & Rekalkulasi Saldo (Hanya Admin / Super Admin)
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403, 'Hanya Admin / Petugas Tabungan yang dapat menghapus transaksi.');
        }

        $tabungan = TabunganSiswa::with('student')->findOrFail($id);
        if ($user->school_id && (int) $tabungan->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        try {
            DB::transaction(function () use ($tabungan) {
                $studentId = $tabungan->student_id;
                $code = $tabungan->code;
                $tabungan->delete();

                $newBalance = TabunganSiswa::recalculateStudentBalance($studentId);
                if ($newBalance < 0) {
                    throw new \RuntimeException("Transaksi {$code} tidak dapat dihapus karena menyebabkan saldo akhir menjadi minus.");
                }
            });
        } catch (\RuntimeException $e) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        $msg = "Transaksi {$tabungan->code} berhasil dibatalkan dan saldo siswa telah disinkronkan kembali.";

        if (request()->expectsJson()) {
            $schoolId = $user->school_id;
            $statsBase = TabunganSiswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId));
            $totalSetorToday = (int) (clone $statsBase)->where('jenis', 'setor')->whereDate('tanggal', today())->sum('nominal');
            $totalTarikToday = (int) (clone $statsBase)->where('jenis', 'tarik')->whereDate('tanggal', today())->sum('nominal');
            $totalTrxToday   = (clone $statsBase)->whereDate('tanggal', today())->count();

            $updatedStudent = Siswa::with('kelas')->find($tabungan->student_id);

            return response()->json([
                'success'    => true,
                'message'    => $msg,
                'deleted_id' => $tabungan->id,
                'student'    => $updatedStudent ? $this->formatStudentLookupPayload($updatedStudent) : null,
                'stats_today' => [
                    'setor'           => $totalSetorToday,
                    'setor_formatted' => 'Rp ' . number_format($totalSetorToday, 0, ',', '.'),
                    'tarik'           => $totalTarikToday,
                    'tarik_formatted' => 'Rp ' . number_format($totalTarikToday, 0, ',', '.'),
                    'trx_count'       => $totalTrxToday,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Halaman Rekapitulasi Saldo Tabungan Siswa Per Kelas
     */
    public function rekap(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $canManage = $user->isAdmin();

        $isWaliKelas = !$canManage && $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $dateFilter = function ($q) use ($startDate, $endDate) {
            if (!empty($startDate)) {
                $q->whereDate('tanggal', '>=', $startDate);
            }
            if (!empty($endDate)) {
                $q->whereDate('tanggal', '<=', $endDate);
            }
        };

        $query = Siswa::with(['kelas', 'latestTabungan'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->withCount(['tabungans as total_transaksi' => $dateFilter])
            ->withSum(['tabungans as total_setor' => function ($q) use ($dateFilter) {
                $dateFilter($q);
                $q->where('jenis', 'setor');
            }], 'nominal')
            ->withSum(['tabungans as total_tarik' => function ($q) use ($dateFilter) {
                $dateFilter($q);
                $q->where('jenis', 'tarik');
            }], 'nominal');

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->where('kelas_id', $request->kelas_id);
        }

        $filterSaldo = $request->input('filter_saldo', 'all');
        if ($filterSaldo === 'has_balance') {
            $query->where('saldo_tabungan', '>', 0);
        } elseif ($filterSaldo === 'no_balance') {
            $query->where('saldo_tabungan', '<=', 0);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                    ->orWhere('nis', 'LIKE', "%{$search}%")
                    ->orWhere('uid_rfid', 'LIKE', "%{$search}%");
            });
        }

        // Statistik Global Rekap sesuai filter kelas
        $statsQuery = Siswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->when($request->filled('kelas_id') && $request->kelas_id !== 'all', fn($q) => $q->where('kelas_id', $request->kelas_id));

        $totalSiswa = (clone $statsQuery)->count();
        $totalPenabung = (clone $statsQuery)->where('saldo_tabungan', '>', 0)->count();
        $totalSaldoKelas = (int) (clone $statsQuery)->sum('saldo_tabungan');

        $trxStatsQuery = TabunganSiswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, function ($q) use ($waliKelasIds) {
                $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));
            })
            ->when($request->filled('kelas_id') && $request->kelas_id !== 'all', function ($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('kelas_id', $request->kelas_id));
            });
        $dateFilter($trxStatsQuery);

        $totalSetorPeriode = (int) (clone $trxStatsQuery)->where('jenis', 'setor')->sum('nominal');
        $totalTarikPeriode = (int) (clone $trxStatsQuery)->where('jenis', 'tarik')->sum('nominal');

        $sort = $request->input('sort', 'saldo_desc');
        if ($sort === 'saldo_asc') {
            $query->orderBy('saldo_tabungan', 'asc')->orderBy('nama', 'asc');
        } elseif ($sort === 'nama_asc') {
            $query->orderBy('nama', 'asc');
        } else {
            $query->orderBy('saldo_tabungan', 'desc')->orderBy('nama', 'asc');
        }

        $students = $query->paginate(25)->withQueryString();

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->orderBy('nama_kelas')
            ->get();

        return view('tabungan.rekap', compact(
            'students',
            'kelasList',
            'canManage',
            'totalSiswa',
            'totalPenabung',
            'totalSaldoKelas',
            'totalSetorPeriode',
            'totalTarikPeriode'
        ));
    }

    /**
     * Halaman Detail Buku Tabungan Digital per Siswa (Admin, Guru, Wali Kelas)
     */
    public function show(Request $request, $studentId)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $canManage = $user->isAdmin();

        $student = Siswa::with('kelas')->findOrFail($studentId);
        if ($schoolId && (int) $student->school_id !== (int) $schoolId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (!$canManage && $user->isWaliKelas() && $user->guru) {
            $waliKelasIds = $user->getManagedKelasIds();
            if (!in_array($student->kelas_id, $waliKelasIds)) {
                abort(403, 'Siswa ini bukan berada di kelas perwalian Anda.');
            }
        }

        return $this->renderStudentPassbook($request, $student, $canManage);
    }

    /**
     * Halaman Buku Tabungan Pribadi untuk Akun Siswa yang Login di Web Utama
     */
    public function myBook(Request $request)
    {
        $user = auth()->user();
        $student = $user->student ?: Siswa::where('user_id', $user->id)->first();

        if (!$student) {
            abort(404, 'Data profil siswa tidak ditemukan untuk akun ini.');
        }

        return $this->renderStudentPassbook($request, $student, false);
    }

    private function renderStudentPassbook(Request $request, Siswa $student, bool $canManage)
    {
        $baseQuery = TabunganSiswa::with('recorder')
            ->where('student_id', $student->id);

        $totalSetor = (int) (clone $baseQuery)->where('jenis', 'setor')->sum('nominal');
        $totalTarik = (int) (clone $baseQuery)->where('jenis', 'tarik')->sum('nominal');
        $totalTrx = (clone $baseQuery)->count();

        if ($request->filled('jenis') && in_array($request->jenis, ['setor', 'tarik'])) {
            $baseQuery->where('jenis', $request->jenis);
        }
        if ($request->filled('start_date')) {
            $baseQuery->whereDate('tanggal', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $baseQuery->whereDate('tanggal', '<=', $request->end_date);
        }

        $mutasi = $baseQuery->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('tabungan.show', compact(
            'student',
            'mutasi',
            'canManage',
            'totalSetor',
            'totalTarik',
            'totalTrx'
        ));
    }

    /**
     * Export Rekap Saldo & Mutasi Tabungan ke Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isWaliKelas = !$user->isAdmin() && $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $mode = $request->input('mode', 'rekap'); // 'rekap' atau 'mutasi'

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($mode === 'mutasi') {
            $sheet->setTitle('Mutasi Tabungan');

            $query = TabunganSiswa::with(['student.kelas', 'recorder'])
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->when($isWaliKelas, function ($q) use ($waliKelasIds) {
                    $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));
                });

            if ($request->filled('jenis') && in_array($request->jenis, ['setor', 'tarik'])) {
                $query->where('jenis', $request->jenis);
            }
            if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
                $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
            }
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            if ($request->filled('tanggal_mulai')) {
                $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
            }
            if ($request->filled('tanggal_selesai')) {
                $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
            }

            $rows = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

            $headers = ['No', 'No. Referensi', 'Tanggal', 'Jam', 'NIS', 'Nama Siswa', 'Kelas', 'Jenis', 'Metode', 'Nominal (Rp)', 'Saldo Akhir (Rp)', 'Keterangan', 'Petugas'];
            $col = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue($col . '1', $h);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }

            $sheet->getStyle('A1:M1')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $r = 2;
            foreach ($rows as $idx => $item) {
                $sheet->setCellValue('A' . $r, $idx + 1);
                $sheet->setCellValue('B' . $r, $item->code);
                $sheet->setCellValue('C' . $r, $item->tanggal ? $item->tanggal->format('d/m/Y') : '-');
                $sheet->setCellValue('D' . $r, $item->waktu ?: '-');
                $sheet->setCellValue('E' . $r, $item->student?->nis ?? '-');
                $sheet->setCellValue('F' . $r, $item->student?->nama ?? '-');
                $sheet->setCellValue('G' . $r, $item->student?->kelas?->nama_kelas ?? '-');
                $sheet->setCellValue('H' . $r, $item->jenis_label);
                $sheet->setCellValue('I' . $r, strtoupper($item->metode ?? 'TUNAI'));
                $sheet->setCellValue('J' . $r, (int) $item->nominal);
                $sheet->setCellValue('K' . $r, (int) $item->saldo_sesudah);
                $sheet->setCellValue('L' . $r, $item->keterangan ?? '-');
                $sheet->setCellValue('M' . $r, $item->recorder?->full_name ?? 'Admin');
                $r++;
            }

            $filename = 'Mutasi_Tabungan_Siswa_' . now()->format('Ymd_His') . '.xlsx';
        } else {
            $sheet->setTitle('Rekap Saldo Tabungan');

            $query = Siswa::with('kelas')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
                ->withCount('tabungans as total_transaksi')
                ->withSum(['tabungans as total_setor' => fn($q) => $q->where('jenis', 'setor')], 'nominal')
                ->withSum(['tabungans as total_tarik' => fn($q) => $q->where('jenis', 'tarik')], 'nominal');

            if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
                $query->where('kelas_id', $request->kelas_id);
            }
            if ($request->input('filter_saldo') === 'has_balance') {
                $query->where('saldo_tabungan', '>', 0);
            }

            $rows = $query->orderBy('saldo_tabungan', 'desc')->orderBy('nama', 'asc')->get();

            $headers = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'Total Setoran (Rp)', 'Total Penarikan (Rp)', 'Jml Transaksi', 'Saldo Akhir (Rp)'];
            $col = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue($col . '1', $h);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }

            $sheet->getStyle('A1:H1')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $r = 2;
            foreach ($rows as $idx => $s) {
                $sheet->setCellValue('A' . $r, $idx + 1);
                $sheet->setCellValue('B' . $r, $s->nis ?? '-');
                $sheet->setCellValue('C' . $r, $s->nama);
                $sheet->setCellValue('D' . $r, $s->kelas?->nama_kelas ?? '-');
                $sheet->setCellValue('E' . $r, (int) ($s->total_setor ?? 0));
                $sheet->setCellValue('F' . $r, (int) ($s->total_tarik ?? 0));
                $sheet->setCellValue('G' . $r, (int) ($s->total_transaksi ?? 0));
                $sheet->setCellValue('H' . $r, (int) $s->saldo_tabungan);
                $r++;
            }

            $filename = 'Rekap_Saldo_Tabungan_Siswa_' . now()->format('Ymd_His') . '.xlsx';
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Cetak PDF Rekap Saldo Tabungan Kelas
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $school = $schoolId ? School::find($schoolId) : null;
        $isWaliKelas = !$user->isAdmin() && $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $query = Siswa::with('kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->withCount('tabungans as total_transaksi')
            ->withSum(['tabungans as total_setor' => fn($q) => $q->where('jenis', 'setor')], 'nominal')
            ->withSum(['tabungans as total_tarik' => fn($q) => $q->where('jenis', 'tarik')], 'nominal');

        $selectedKelas = null;
        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->where('kelas_id', $request->kelas_id);
            $selectedKelas = Kelas::find($request->kelas_id);
        }

        if ($request->input('filter_saldo') === 'has_balance') {
            $query->where('saldo_tabungan', '>', 0);
        }

        $students = $query->orderBy('saldo_tabungan', 'desc')->orderBy('nama', 'asc')->get();

        $pdf = Pdf::loadView('tabungan.pdf-rekap', [
            'students'      => $students,
            'school'        => $school,
            'selectedKelas' => $selectedKelas,
            'printedBy'     => $user->full_name ?? 'Admin',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Rekap_Tabungan_Siswa_' . now()->format('Ymd') . '.pdf');
    }

    /**
     * Cetak PDF Buku Tabungan Per Siswa
     */
    public function printStudentBookPdf(Request $request, $studentId)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $school = $schoolId ? School::find($schoolId) : null;

        $student = Siswa::with('kelas')->findOrFail($studentId);
        if ($schoolId && (int) $student->school_id !== (int) $schoolId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        // Jika role siswa, hanya boleh cetak bukunya sendiri
        if (in_array($user->role, ['student', 'siswa']) && (int) $student->user_id !== (int) $user->id) {
            abort(403, 'Anda hanya dapat mencetak buku tabungan Anda sendiri.');
        }

        $mutasi = TabunganSiswa::with('recorder')
            ->where('student_id', $student->id)
            ->orderBy('tanggal', 'asc')
            ->orderBy('waktu', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalSetor = (int) $mutasi->where('jenis', 'setor')->sum('nominal');
        $totalTarik = (int) $mutasi->where('jenis', 'tarik')->sum('nominal');

        $pdf = Pdf::loadView('tabungan.pdf-buku', [
            'student'    => $student,
            'mutasi'     => $mutasi,
            'school'     => $school,
            'totalSetor' => $totalSetor,
            'totalTarik' => $totalTarik,
            'printedBy'  => $user->full_name ?? $student->nama,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Buku_Tabungan_' . str_replace(' ', '_', $student->nama) . '.pdf');
    }
}
