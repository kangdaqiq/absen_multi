<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiLog;

class ApiLogController extends Controller
{
    public function index()
    {
        $logs = ApiLog::orderBy('created_at', 'desc')->paginate(20);
        return view('api_logs.index', compact('logs'));
    }
}
