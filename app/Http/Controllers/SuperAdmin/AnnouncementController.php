<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $announcements = Announcement::latest()->paginate(15);
        return view('super-admin.announcements.index', compact('announcements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('super-admin.announcements.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:info,release,feature,warning,maintenance',
            'version' => 'nullable|string|max:50',
            'content' => 'required|string',
            'action_url' => 'nullable|url|max:255',
            'action_text' => 'nullable|string|max:100',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_popup'] = $request->boolean('is_popup');

        Announcement::create($validated);

        return redirect()->route('super-admin.announcements.index')
            ->with('success', 'Pengumuman / Update rilis berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Announcement $announcement)
    {
        return view('super-admin.announcements.edit', compact('announcement'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:info,release,feature,warning,maintenance',
            'version' => 'nullable|string|max:50',
            'content' => 'required|string',
            'action_url' => 'nullable|url|max:255',
            'action_text' => 'nullable|string|max:100',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_popup'] = $request->boolean('is_popup');

        $announcement->update($validated);

        return redirect()->route('super-admin.announcements.index')
            ->with('success', 'Pengumuman / Update rilis berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('super-admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }
}
