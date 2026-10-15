<?php

namespace App\Http\Controllers;

use App\Models\JamPelajaran;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JamPelajaranController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = JamPelajaran::withCount('jadwalPelajarans');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('hari')) {
            $hariFilter = trim($request->hari);
            $query->where(function ($q) use ($hariFilter) {
                $q->where('hari', 'like', "%{$hariFilter}%")
                  ->orWhereNull('hari')
                  ->orWhere('hari', '')
                  ->orWhere('hari', 'like', '%semua hari%');
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_jam', 'like', "%{$search}%")
                  ->orWhere('jam_ke', 'like', "%{$search}%")
                  ->orWhere('jam_mulai', 'like', "%{$search}%")
                  ->orWhere('jam_selesai', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhere('hari', 'like', "%{$search}%");
            });
        }

        $jamPelajarans = $query->orderBy('urutan', 'asc')
            ->orderBy('jam_mulai', 'asc')
            ->get();

        $groupedSessions = $jamPelajarans->groupBy(function ($item) {
            return $item->nama_jam ?: 'Sesi Reguler';
        })->map(function ($items, $namaSesi) {
            $minMulai = $items->min('jam_mulai');
            $maxSelesai = $items->max('jam_selesai');

            $rentangWaktu = ($minMulai && $maxSelesai)
                ? substr($minMulai, 0, 5) . ' - ' . substr($maxSelesai, 0, 5)
                : '-';

            $totalDurasi = $items->sum(function ($j) {
                return $j->durasi_menit;
            });

            $hariUnique = [];
            foreach ($items as $item) {
                foreach ($item->hari_array as $h) {
                    if (!in_array($h, $hariUnique)) {
                        $hariUnique[] = $h;
                    }
                }
            }

            $activeCount = $items->where('is_active', true)->count();
            $totalCount = $items->count();
            $totalJadwal = $items->sum('jadwal_pelajarans_count');

            $jamKeList = $items->pluck('jam_ke')->filter()->values();
            $labelJamKe = '';
            if ($jamKeList->isNotEmpty()) {
                $firstJamKe = $jamKeList->first();
                $lastJamKe = $jamKeList->last();
                if ($firstJamKe == $lastJamKe) {
                    $labelJamKe = "Jam Ke-{$firstJamKe}";
                } else {
                    $labelJamKe = "Jam Ke-{$firstJamKe} s/d {$lastJamKe}";
                }
            }

            return (object) [
                'nama' => $namaSesi,
                'items' => $items,
                'total_jam' => $totalCount,
                'label_jam_ke' => $labelJamKe,
                'rentang_waktu' => $rentangWaktu,
                'total_durasi' => $totalDurasi,
                'hari_unique' => $hariUnique,
                'active_count' => $activeCount,
                'total_jadwal' => $totalJadwal,
                'is_all_active' => ($activeCount === $totalCount),
            ];
        });

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return view('jam-pelajaran.index', compact('jamPelajarans', 'groupedSessions', 'hariList'));
    }

    /**
     * Helper untuk format input hari (array maupun string)
     */
    protected function formatHariInput($hariInput): ?string
    {
        if (is_array($hariInput)) {
            $cleaned = array_values(array_filter(array_map('trim', $hariInput)));
            if (empty($cleaned) || count($cleaned) >= 7) {
                return null;
            }
            return implode(', ', $cleaned);
        }

        if (is_string($hariInput)) {
            $trimmed = trim($hariInput);
            if ($trimmed === '' || $trimmed === 'all' || $trimmed === 'no_change') {
                return null;
            }
            return $trimmed;
        }

        return null;
    }

    public function store(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'nama_jam' => 'required|string|max:100',
            'jam_ke' => 'nullable|string|max:50',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'hari' => 'nullable',
            'urutan' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $urutan = $request->input('urutan');
        if (!$urutan) {
            $last = JamPelajaran::where('school_id', $schoolId)->max('urutan');
            $urutan = ($last ?? 0) + 1;
        }

        $hari = $this->formatHariInput($request->input('hari'));

        JamPelajaran::create([
            'school_id' => $schoolId,
            'nama_jam' => $request->nama_jam,
            'jam_ke' => $request->jam_ke ?: null,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'hari' => $hari,
            'urutan' => $urutan,
            'is_active' => $request->has('is_active') ? true : true,
            'keterangan' => $request->keterangan ?: null,
        ]);

        return redirect()->route('jam-pelajaran.index')
            ->with('success', 'Jam pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = JamPelajaran::where('id', $id);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        $jam = $query->firstOrFail();

        $request->validate([
            'nama_jam' => 'required|string|max:100',
            'jam_ke' => 'nullable|string|max:50',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'hari' => 'nullable',
            'urutan' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $hari = $this->formatHariInput($request->input('hari'));

        $jam->update([
            'nama_jam' => $request->nama_jam,
            'jam_ke' => $request->jam_ke ?: null,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'hari' => $hari,
            'urutan' => $request->urutan ?: $jam->urutan,
            'is_active' => $request->has('is_active'),
            'keterangan' => $request->keterangan ?: null,
        ]);

        return redirect()->route('jam-pelajaran.index')
            ->with('success', 'Jam pelajaran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = JamPelajaran::where('id', $id);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        $jam = $query->firstOrFail();

        $jam->delete();

        return redirect()->route('jam-pelajaran.index')
            ->with('success', 'Jam pelajaran berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = JamPelajaran::where('id', $id);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        $jam = $query->firstOrFail();

        $jam->is_active = !$jam->is_active;
        $jam->save();

        $status = $jam->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('jam-pelajaran.index')
            ->with('success', "Jam pelajaran {$jam->nama_jam} berhasil {$status}.");
    }

    /**
     * Generate Preset Otomatis Jam Pelajaran
     */
    public function generatePresets(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'nama_sesi_prefix' => 'nullable|string|max:50',
            'jam_mulai_pertama' => 'required',
            'durasi_menit' => 'required',
            'durasi_menit_custom' => 'nullable|integer|min:10|max:180',
            'jumlah_jam' => 'required|integer|min:1|max:20',
            'hari' => 'nullable',
            'jumlah_istirahat' => 'nullable|integer|min:0|max:5',
            'istirahat' => 'nullable|array',
            'istirahat.*.setelah_jam' => 'nullable|integer|min:1',
            'istirahat.*.durasi' => 'nullable|integer|min:5|max:120',
        ]);

        $prefix = trim($request->input('nama_sesi_prefix', ''));
        $durasi = $request->durasi_menit === 'custom'
            ? (int)$request->durasi_menit_custom
            : (int)$request->durasi_menit;

        if ($durasi <= 0) {
            $durasi = 45;
        }

        $total = (int)$request->jumlah_jam;
        $curr = Carbon::parse($request->jam_mulai_pertama);
        $hari = $this->formatHariInput($request->input('hari'));

        // Ambil mapping istirahat: setelah_jam => durasi_menit
        $breaksMap = [];
        if ($request->has('istirahat') && is_array($request->istirahat)) {
            foreach ($request->istirahat as $item) {
                $afterJam = !empty($item['setelah_jam']) ? (int)$item['setelah_jam'] : null;
                $durasiIstirahat = !empty($item['durasi']) ? (int)$item['durasi'] : 0;
                if ($afterJam && $durasiIstirahat > 0) {
                    $breaksMap[$afterJam] = $durasiIstirahat;
                }
            }
        }

        // Ambil urutan terakhir di sekolah ini agar urutan berlanjut rapi
        $lastUrutan = JamPelajaran::where('school_id', $schoolId)->max('urutan') ?? 0;

        for ($i = 1; $i <= $total; $i++) {
            $startStr = $curr->format('H:i');
            $curr->addMinutes($durasi);
            $endStr = $curr->format('H:i');

            // Format nama_jam: Cukup nama sesi saja jika diisi (tanpa jam ke), default: Jam Ke x
            $namaJam = $prefix !== '' ? $prefix : "Jam Ke {$i}";
            $keterangan = $prefix !== '' ? "Sesi {$prefix} Jam ke-{$i}" : "Sesi Reguler Jam ke-{$i}";

            JamPelajaran::create([
                'school_id' => $schoolId,
                'nama_jam' => $namaJam,
                'jam_ke' => (string)$i,
                'jam_mulai' => $startStr,
                'jam_selesai' => $endStr,
                'hari' => $hari,
                'urutan' => $lastUrutan + $i,
                'is_active' => true,
                'keterangan' => $keterangan,
            ]);

            // Jika ada istirahat setelah jam ke-$i
            if (isset($breaksMap[$i])) {
                $curr->addMinutes($breaksMap[$i]);
            }
        }

        return redirect()->route('jam-pelajaran.index')
            ->with('success', "Berhasil membuat {$total} sesi jam pelajaran otomatis" . ($prefix ? " ({$prefix})" : "") . ".");
    }

    public function bulkUpdate(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'jam_ids' => 'required|array',
            'jam_ids.*' => 'exists:jam_pelajaran,id',
            'hari_mode' => 'nullable|string',
            'hari' => 'nullable',
            'is_active' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $ids = $request->jam_ids;
        $updates = [];

        if ($request->filled('hari_mode') && $request->hari_mode !== 'no_change') {
            if ($request->hari_mode === 'all') {
                $updates['hari'] = null;
            } elseif ($request->hari_mode === 'custom') {
                $updates['hari'] = $this->formatHariInput($request->input('hari'));
            }
        } elseif ($request->filled('hari') && $request->hari !== 'no_change') {
            $updates['hari'] = $this->formatHariInput($request->input('hari'));
        }

        if ($request->filled('is_active') && $request->is_active !== 'no_change') {
            $updates['is_active'] = $request->is_active === '1';
        }

        if ($request->filled('keterangan') && $request->keterangan !== 'no_change') {
            $updates['keterangan'] = $request->keterangan === 'null' ? null : $request->keterangan;
        }

        if (!empty($updates)) {
            $query = JamPelajaran::whereIn('id', $ids);
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }
            $query->update($updates);
        }

        return back()->with('success', count($ids) . ' sesi jam pelajaran berhasil diperbarui secara massal.');
    }

    public function bulkDestroy(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'jam_ids' => 'required|array',
            'jam_ids.*' => 'exists:jam_pelajaran,id',
        ]);

        $ids = $request->jam_ids;

        // Lepas foreign key pada jadwal pelajaran
        if (class_exists(\App\Models\JadwalPelajaran::class)) {
            \App\Models\JadwalPelajaran::whereIn('jam_pelajaran_id', $ids)->update(['jam_pelajaran_id' => null]);
        }

        $query = JamPelajaran::whereIn('id', $ids);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $count = $query->delete();

        return back()->with('success', "$count sesi jam pelajaran berhasil dihapus secara massal.");
    }
}
