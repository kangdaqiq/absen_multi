<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplatePesanController extends Controller
{
    /**
     * Display all message templates categorized for the current school
     */
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? 0;

        // If super admin and school_id query parameter is present, allow switching school
        if (auth()->user()->isSuperAdmin() && $request->has('school_id')) {
            $schoolId = (int) $request->get('school_id');
        }

        // For self-hosted fallback
        if (!$schoolId && config('app.mode') === 'self_hosted') {
            $firstSchool = School::first();
            $schoolId = $firstSchool ? $firstSchool->id : 0;
        }

        $templates = NotificationTemplate::where('school_id', $schoolId)
            ->orderBy('category')
            ->orderBy('id')
            ->get()
            ->groupBy('category');

        $categories = NotificationTemplate::CATEGORIES;
        $placeholders = NotificationTemplate::PLACEHOLDERS;

        $schools = auth()->user()->isSuperAdmin() ? School::all() : collect();
        $currentSchool = School::find($schoolId);

        return view('settings.templates.index', compact(
            'templates',
            'categories',
            'placeholders',
            'schoolId',
            'schools',
            'currentSchool'
        ));
    }

    /**
     * Store a new template variation
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'title'    => 'nullable|string|max:100',
            'content'  => 'required|string',
        ]);

        $schoolId = auth()->user()->school_id ?? 0;
        if (auth()->user()->isSuperAdmin() && $request->filled('school_id')) {
            $schoolId = (int) $request->school_id;
        }

        if (!$schoolId && config('app.mode') === 'self_hosted') {
            $schoolId = School::first()?->id ?? 0;
        }

        if (!$schoolId) {
            return back()->with('error', 'School ID tidak ditemukan.');
        }

        // Count existing variations in this category to generate title if empty
        $count = NotificationTemplate::where('school_id', $schoolId)
            ->where('category', $request->category)
            ->count();

        $title = $request->title ?: ('Variasi ' . ($count + 1));

        NotificationTemplate::create([
            'school_id' => $schoolId,
            'category'  => $request->category,
            'title'     => $title,
            'content'   => $request->content,
            'is_active' => true,
        ]);

        return back()->with('success', 'Variasi template berhasil ditambahkan.');
    }

    /**
     * Update an existing template variation
     */
    public function update(Request $request, NotificationTemplate $template)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        if (auth()->user()->isSuperAdmin()) {
            $schoolId = $template->school_id;
        }

        if ($template->school_id !== $schoolId && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'title'     => 'nullable|string|max:100',
            'content'   => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update([
            'title'     => $request->title ?: $template->title,
            'content'   => $request->content,
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : $template->is_active,
        ]);

        return back()->with('success', 'Template berhasil diperbarui.');
    }

    /**
     * Toggle active status
     */
    public function toggle(NotificationTemplate $template)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        if ($template->school_id !== $schoolId && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $template->update([
            'is_active' => !$template->is_active
        ]);

        return back()->with('success', 'Status variasi template berhasil diubah.');
    }

    /**
     * Delete a template variation
     */
    public function destroy(NotificationTemplate $template)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        if ($template->school_id !== $schoolId && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $template->delete();

        return back()->with('success', 'Variasi template berhasil dihapus.');
    }

    /**
     * Reset all custom variations for a category (falls back to system defaults)
     */
    public function resetCategory(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
        ]);

        $schoolId = auth()->user()->school_id ?? 0;
        if (auth()->user()->isSuperAdmin() && $request->filled('school_id')) {
            $schoolId = (int) $request->school_id;
        }

        NotificationTemplate::where('school_id', $schoolId)
            ->where('category', $request->category)
            ->delete();

        return back()->with('success', 'Custom template kategori ini telah direset ke default sistem.');
    }

    /**
     * Live Preview AJAX endpoint
     */
    public function preview(Request $request)
    {
        $content = $request->input('content', '');
        $category = $request->input('category', 'checkin_siswa');

        $isParent = str_contains($category, 'ortu');
        $dummyName = $isParent ? 'Ahmad Pratama' : 'Ahmad Pratama';
        $schoolName = auth()->user()->school?->name ?? 'SMK Assuniyah Tumijajar';

        $dummyVars = [
            '{nama}'                => $dummyName,
            '{nis}'                 => '12345678',
            '{kelas}'               => 'XII RPL 1',
            '{tanggal}'             => now()->format('d/m/Y'),
            '{jam_masuk}'           => '07:15',
            '{jam_pulang}'          => '15:30',
            '{durasi}'              => '8 jam 15 menit',
            '{durasi_terlambat}'    => '20 menit',
            '{durasi_pulang_cepat}' => '45 menit',
            '{status}'              => str_contains($category, 'sakit') ? 'Sakit' : (str_contains($category, 'izin') ? 'Izin' : (str_contains($category, 'late') ? 'Terlambat' : 'Hadir')),
            '{nama_sekolah}'        => $schoolName,
            '{diotorisasi}'         => 'Bpk. Budi Santoso, S.Pd',
            '{salam}'               => $isParent ? "Yth. Orang Tua/Wali *{$dummyName}*," : "Halo, *{$dummyName}* 👋,",
            '{penutup}'             => $isParent ? "_\n\nSilakan balas pesan ini dengan kata *OK* sebagai konfirmasi Anda._" : "_\n\nSilakan balas pesan ini singkat saja (contoh: *OK*) untuk konfirmasi Anda._",
        ];

        $rendered = strtr($content, $dummyVars);

        return response()->json([
            'ok'       => true,
            'rendered' => $rendered,
        ]);
    }
}
