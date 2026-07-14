<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramLog;

class TelegramLogController extends Controller
{
    public function index(Request $request)
    {
        $query = TelegramLog::orderBy('created_at', 'desc');

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
            $query->where('chat_id', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
        }

        $logs = $query->paginate(20)->withQueryString();
        return view('telegram.logs', compact('logs'));
    }
}
