<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MessageQueue;

class WhatsappLogController extends Controller
{
    public function index()
    {
        $logs = MessageQueue::orderBy('created_at', 'desc')->paginate(20);
        return view('whatsapp.logs', compact('logs'));
    }
}
