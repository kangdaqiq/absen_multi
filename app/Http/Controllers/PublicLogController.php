<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiLog;
use App\Models\School;
use Carbon\Carbon;

class PublicLogController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        
        // Quick Stats Today
        $totalLogsToday = ApiLog::whereDate('created_at', $today)->count();
        $successLogsToday = ApiLog::whereDate('created_at', $today)->where('success', true)->count();
        $failedLogsToday = ApiLog::whereDate('created_at', $today)->where('success', false)->count();
        $uniqueIpsToday = ApiLog::whereDate('created_at', $today)->distinct('ip_address')->count('ip_address');

        return view('public_logs.index', compact(
            'totalLogsToday',
            'successLogsToday',
            'failedLogsToday',
            'uniqueIpsToday',
            'today'
        ));
    }

    public function getData(Request $request)
    {
        $limit = min((int)$request->input('limit', 50), 100);
        $search = $request->input('search');
        $category = $request->input('category', 'all');
        $status = $request->input('status', 'all');
        $date = $request->input('date');

        $query = ApiLog::with('school')->orderBy('created_at', 'desc');

        // Filter Date
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        // Filter Category
        if ($category === 'mobile') {
            $query->where(function ($q) {
                $q->where('action', 'like', 'mobile%')
                  ->orWhere('api_key', 'MOBILE_APP');
            });
        } elseif ($category === 'rfid') {
            $query->where(function ($q) {
                $q->where('action', 'like', 'rfid%')
                  ->orWhere('action', 'tap')
                  ->orWhere('action', 'scan');
            });
        } elseif ($category === 'fingerprint') {
            $query->where(function ($q) {
                $q->where('action', 'like', 'r307%')
                  ->orWhere('action', 'like', 'finger%')
                  ->orWhere('action', 'like', 'as608%');
            });
        } elseif ($category === 'auth_failed') {
            $query->where('action', 'auth_failed')
                  ->orWhere('action', 'like', '%failed');
        }

        // Filter Status
        if ($status === 'success') {
            $query->where('success', true);
        } elseif ($status === 'failed') {
            $query->where('success', false);
        }

        // Filter Search Keyword
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('uid', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('api_key', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        $logs = $query->limit($limit)->get()->map(function ($log) {
            $created = $log->created_at ? Carbon::parse($log->created_at) : null;
            return [
                'id' => $log->id,
                'api_key' => $log->api_key ?? 'SYSTEM',
                'action' => $log->action ?? 'request',
                'uid' => $log->uid,
                'success' => (bool)$log->success,
                'message' => $log->message,
                'ip_address' => $log->ip_address ?? '0.0.0.0',
                'user_agent' => $log->user_agent,
                'school_name' => $log->school?->name ?? 'Pusat / Global',
                'time_full' => $created ? $created->translatedFormat('d M Y, H:i:s') : '-',
                'time_short' => $created ? $created->format('H:i:s') : '-',
                'time_ago' => $created ? $created->diffForHumans() : '-',
            ];
        });

        // Current Stats
        $today = Carbon::today()->format('Y-m-d');
        $stats = [
            'total_today' => ApiLog::whereDate('created_at', $today)->count(),
            'success_today' => ApiLog::whereDate('created_at', $today)->where('success', true)->count(),
            'failed_today' => ApiLog::whereDate('created_at', $today)->where('success', false)->count(),
            'unique_ips_today' => ApiLog::whereDate('created_at', $today)->distinct('ip_address')->count('ip_address'),
            'server_time' => Carbon::now()->translatedFormat('d F Y, H:i:s'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'data' => $logs
        ]);
    }

    public function testPing(Request $request)
    {
        $type = $request->input('type', 'mobile');
        
        if ($type === 'mobile') {
            $log = ApiLog::create([
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_test_ping',
                'uid' => 'DEMO_USER_01',
                'success' => true,
                'message' => 'Simulasi Request Mobile App: Ping Diagnostic Test Berhasil',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Test App/1.0',
                'created_at' => now(),
            ]);
        } elseif ($type === 'rfid') {
            $log = ApiLog::create([
                'api_key' => 'DEVICE_RFID_TEST',
                'action' => 'rfid_tap_test',
                'uid' => 'A1B2C3D4',
                'success' => true,
                'message' => 'Simulasi Request Reader RFID: Kartu Ditempel (UID: A1B2C3D4)',
                'ip_address' => $request->ip(),
                'user_agent' => 'ESP8266-RFID-Client/1.0',
                'created_at' => now(),
            ]);
        } else {
            $log = ApiLog::create([
                'api_key' => 'UNKNOWN_KEY',
                'action' => 'auth_failed',
                'uid' => null,
                'success' => false,
                'message' => 'Simulasi Request Gagal: API Key tidak valid / Device Unauthorized',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Log tes berhasil dikirim!',
            'log' => $log
        ]);
    }

    public function clearLogs(Request $request)
    {
        ApiLog::truncate();
        return response()->json([
            'success' => true,
            'message' => 'Semua riwayat API log berhasil dibersihkan'
        ]);
    }
}
