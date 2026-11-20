<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TabunganSiswa;
use Illuminate\Http\Request;

class MobileTabunganController extends Controller
{
    /**
     * API Buku Tabungan Saya (Untuk Siswa)
     * GET /api/mobile/tabungan/my
     */
    public function myTabungan(Request $request)
    {
        $user = $request->user();
        $student = $user->student ?: Siswa::with('kelas')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data siswa tidak ditemukan untuk akun ini.',
            ], 404);
        }

        $query = TabunganSiswa::with('recorder:id,full_name')
            ->where('student_id', $student->id);

        $totalSetor = (int) (clone $query)->where('jenis', 'setor')->sum('nominal');
        $totalTarik = (int) (clone $query)->where('jenis', 'tarik')->sum('nominal');
        $totalTrx = (clone $query)->count();

        if ($request->filled('jenis') && in_array($request->jenis, ['setor', 'tarik'])) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        $limit = min((int) $request->input('limit', 50), 100);
        $mutasi = $query->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($t) => [
                'id'                      => $t->id,
                'code'                    => $t->code,
                'tanggal'                 => $t->tanggal ? $t->tanggal->format('Y-m-d') : null,
                'tanggal_formatted'       => $t->tanggal ? $t->tanggal->translatedFormat('d F Y') : '-',
                'waktu'                   => $t->waktu ?: '-',
                'jenis'                   => $t->jenis,
                'jenis_label'             => $t->jenis_label,
                'nominal'                 => (int) $t->nominal,
                'nominal_formatted'       => $t->nominal_formatted,
                'saldo_sebelum'           => (int) $t->saldo_sebelum,
                'saldo_sebelum_formatted' => $t->saldo_sebelum_formatted,
                'saldo_sesudah'           => (int) $t->saldo_sesudah,
                'saldo_sesudah_formatted' => $t->saldo_sesudah_formatted,
                'metode'                  => $t->metode,
                'keterangan'              => $t->keterangan ?: ($t->jenis === 'setor' ? 'Setoran Tabungan' : 'Penarikan Tabungan'),
                'petugas'                 => $t->recorder?->full_name ?? 'Petugas Tabungan',
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data buku tabungan berhasil dimuat.',
            'data'    => [
                'student' => [
                    'id'              => $student->id,
                    'nama'            => $student->nama,
                    'nis'             => $student->nis ?? '-',
                    'kelas'           => $student->kelas?->nama_kelas ?? '-',
                    'saldo'           => (int) $student->saldo_tabungan,
                    'saldo_formatted' => 'Rp ' . number_format((int) $student->saldo_tabungan, 0, ',', '.'),
                    'foto_url'        => $student->foto_url,
                ],
                'summary' => [
                    'saldo_saat_ini'        => (int) $student->saldo_tabungan,
                    'saldo_formatted'       => 'Rp ' . number_format((int) $student->saldo_tabungan, 0, ',', '.'),
                    'total_setoran'         => $totalSetor,
                    'total_setoran_formatted' => 'Rp ' . number_format($totalSetor, 0, ',', '.'),
                    'total_tarikan'         => $totalTarik,
                    'total_tarikan_formatted' => 'Rp ' . number_format($totalTarik, 0, ',', '.'),
                    'total_transaksi'       => $totalTrx,
                ],
                'mutasi' => $mutasi,
            ],
        ]);
    }

    /**
     * API Rekap & Monitoring Tabungan Kelas (Untuk Guru / Wali Kelas)
     * GET /api/mobile/guru/tabungan
     */
    public function teacherIndex(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;
        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas']);

        $selectedKelasId = $request->input('kelas_id');
        if (!$selectedKelasId && $isWaliKelas && count($waliKelasIds) > 0) {
            $selectedKelasId = $waliKelasIds[0];
        }

        $query = Siswa::with('kelas:id,nama_kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->when($selectedKelasId && $selectedKelasId !== 'all', fn($q) => $q->where('kelas_id', $selectedKelasId));

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('nis', 'LIKE', "%{$q}%");
            });
        }

        // Statistik Kelas
        $statsBase = clone $query;
        $totalSiswa = (clone $statsBase)->count();
        $totalSaldoKelas = (int) (clone $statsBase)->sum('saldo_tabungan');
        $jumlahPenabung = (clone $statsBase)->where('saldo_tabungan', '>', 0)->count();

        // Mutasi Bulan Ini untuk Kelas Tersebut
        $trxQuery = TabunganSiswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds)))
            ->when($selectedKelasId && $selectedKelasId !== 'all', fn($q) => $q->whereHas('student', fn($sq) => $sq->where('kelas_id', $selectedKelasId)))
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year);

        $setorBulanIni = (int) (clone $trxQuery)->where('jenis', 'setor')->sum('nominal');
        $tarikBulanIni = (int) (clone $trxQuery)->where('jenis', 'tarik')->sum('nominal');

        $students = $query->orderBy('saldo_tabungan', 'desc')
            ->orderBy('nama', 'asc')
            ->limit(100)
            ->get()
            ->map(fn($s) => [
                'id'              => $s->id,
                'nama'            => $s->nama,
                'nis'             => $s->nis ?? '-',
                'kelas_id'        => $s->kelas_id,
                'kelas_nama'      => $s->kelas?->nama_kelas ?? '-',
                'saldo'           => (int) $s->saldo_tabungan,
                'saldo_formatted' => 'Rp ' . number_format((int) $s->saldo_tabungan, 0, ',', '.'),
                'foto_url'        => $s->foto_url,
            ]);

        // 10 Transaksi Terakhir di Kelas
        $recentTrx = TabunganSiswa::with(['student:id,nama,nis,kelas_id', 'student.kelas:id,nama_kelas', 'recorder:id,full_name'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds)))
            ->when($selectedKelasId && $selectedKelasId !== 'all', fn($q) => $q->whereHas('student', fn($sq) => $sq->where('kelas_id', $selectedKelasId)))
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id'                => $t->id,
                'code'              => $t->code,
                'student_nama'      => $t->student?->nama ?? '-',
                'student_kelas'     => $t->student?->kelas?->nama_kelas ?? '-',
                'tanggal'           => $t->tanggal ? $t->tanggal->format('d/m/Y') : '-',
                'waktu'             => $t->waktu ?: '-',
                'jenis'             => $t->jenis,
                'nominal'           => (int) $t->nominal,
                'nominal_formatted' => $t->nominal_formatted,
                'saldo_sesudah'     => (int) $t->saldo_sesudah,
                'keterangan'        => $t->keterangan,
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data tabungan kelas berhasil dimuat.',
            'data'    => [
                'selected_kelas_id' => $selectedKelasId ? (int) $selectedKelasId : null,
                'kelas_list'        => $kelasList,
                'summary'           => [
                    'total_siswa'            => $totalSiswa,
                    'total_penabung'         => $jumlahPenabung,
                    'total_saldo_kelas'      => $totalSaldoKelas,
                    'total_saldo_formatted'  => 'Rp ' . number_format($totalSaldoKelas, 0, ',', '.'),
                    'setor_bulan_ini'        => $setorBulanIni,
                    'setor_bulan_formatted'  => 'Rp ' . number_format($setorBulanIni, 0, ',', '.'),
                    'tarik_bulan_ini'        => $tarikBulanIni,
                    'tarik_bulan_formatted'  => 'Rp ' . number_format($tarikBulanIni, 0, ',', '.'),
                ],
                'students'   => $students,
                'recent_trx' => $recentTrx,
            ],
        ]);
    }

    /**
     * API Detail Mutasi Satu Siswa untuk Guru
     * GET /api/mobile/guru/tabungan/student/{id}
     */
    public function teacherStudentDetail(Request $request, $id)
    {
        $user = $request->user();
        $student = Siswa::with('kelas')->findOrFail($id);

        if ($user->school_id && (int) $student->school_id !== (int) $user->school_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $query = TabunganSiswa::with('recorder:id,full_name')
            ->where('student_id', $student->id);

        $totalSetor = (int) (clone $query)->where('jenis', 'setor')->sum('nominal');
        $totalTarik = (int) (clone $query)->where('jenis', 'tarik')->sum('nominal');

        $mutasi = $query->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($t) => [
                'id'                => $t->id,
                'code'              => $t->code,
                'tanggal'           => $t->tanggal ? $t->tanggal->format('d/m/Y') : '-',
                'waktu'             => $t->waktu ?: '-',
                'jenis'             => $t->jenis,
                'jenis_label'       => $t->jenis_label,
                'nominal'           => (int) $t->nominal,
                'nominal_formatted' => $t->nominal_formatted,
                'saldo_sesudah'     => (int) $t->saldo_sesudah,
                'saldo_formatted'   => $t->saldo_sesudah_formatted,
                'keterangan'        => $t->keterangan,
                'petugas'           => $t->recorder?->full_name ?? 'Petugas Tabungan',
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail tabungan siswa berhasil dimuat.',
            'data'    => [
                'student' => [
                    'id'              => $student->id,
                    'nama'            => $student->nama,
                    'nis'             => $student->nis ?? '-',
                    'kelas'           => $student->kelas?->nama_kelas ?? '-',
                    'saldo'           => (int) $student->saldo_tabungan,
                    'saldo_formatted' => 'Rp ' . number_format((int) $student->saldo_tabungan, 0, ',', '.'),
                    'foto_url'        => $student->foto_url,
                    'total_setor'     => $totalSetor,
                    'total_tarik'     => $totalTarik,
                ],
                'mutasi' => $mutasi,
            ],
        ]);
    }
}
