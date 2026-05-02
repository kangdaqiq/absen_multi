<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiLog;

class ApiLogController extends Controller
{
    public function index()
    {
        $query = ApiLog::orderBy('created_at', 'desc');

        if (!auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                // Since api_logs now has school_id, filter directly
                $query->where('school_id', $schoolId);
            } else {
                // If not super admin and no school (rare/error), show empty
                $query->whereRaw('0 = 1');
            }
        }

        $logs = $query->paginate(20);
        return view('api_logs.index', compact('logs'));
    }
}
