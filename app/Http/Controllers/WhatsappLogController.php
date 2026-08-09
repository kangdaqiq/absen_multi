<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MessageQueue;

class WhatsappLogController extends Controller
{
    public function index(Request $request)
    {
        $query = MessageQueue::orderBy('created_at', 'desc');

        if (!auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();
        return view('whatsapp.logs', compact('logs'));
    }

    public function clearPending()
    {
        $query = MessageQueue::whereIn('status', ['pending', 'processing', 'failed']);

        if (!auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            } else {
                return back()->with('error', 'Akses ditolak.');
            }
        }

        $count = $query->delete();
        return back()->with('success', "Berhasil menghapus {$count} pesan antrean (pending & failed).");
    }

    public function clearAll()
    {
        $query = MessageQueue::query();

        if (!auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            } else {
                return back()->with('error', 'Akses ditolak.');
            }
        }

        $count = $query->delete();
        return back()->with('success', "Berhasil menghapus semua log antrean WA ({$count} data).");
    }
}
