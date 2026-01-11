<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MessageQueue;

class WhatsappLogController extends Controller
{
    public function index()
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

        $logs = $query->paginate(20);
        return view('whatsapp.logs', compact('logs'));
    }
}
