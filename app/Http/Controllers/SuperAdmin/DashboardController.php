<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;
use App\Services\StorageServerService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(StorageServerService $storageService)
    {
        // Get statistics for all schools
        $totalSchools = School::count();
        $activeSchools = School::where('is_active', true)->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalStudents = Siswa::count();
        $totalTeachers = Guru::count();

        // Get recent schools
        $recentSchools = School::latest()->take(5)->get();

        // Get schools with student count
        $schoolsWithStats = School::withCount(['siswa', 'guru', 'users'])
            ->where('is_active', true)
            ->get();

        $popupAnnouncements = \App\Models\Announcement::where('is_active', true)
            ->where('is_popup', true)
            ->latest()
            ->take(5)
            ->get();

        // Storage Server & Quota metrics
        $storageHealth = $storageService->getHealth();
        $storageOnline = !empty($storageHealth);
        $dokKbmSchoolsCount = School::where('dok_kbm_enabled', true)->count();
        $totalAllocatedQuotaMb = School::sum('storage_quota_mb');

        $storageStats = [
            'is_online'             => $storageOnline,
            'dok_kbm_schools_count' => $dokKbmSchoolsCount,
            'total_quota_human'     => ($totalAllocatedQuotaMb >= 1024)
                ? round($totalAllocatedQuotaMb / 1024, 2) . ' GB'
                : $totalAllocatedQuotaMb . ' MB',
            'server_version'        => $storageHealth['version'] ?? '1.1.0',
            'server_url'            => $storageService->getBaseUrl(),
        ];

        return view('super-admin.dashboard', compact(
            'totalSchools',
            'activeSchools',
            'totalAdmins',
            'totalStudents',
            'totalTeachers',
            'recentSchools',
            'schoolsWithStats',
            'popupAnnouncements',
            'storageStats'
        ));
    }
}
