<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;
use App\Services\R2StorageService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(R2StorageService $r2Service)
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

        // Cloudflare R2 Storage & Quota metrics
        $globalStats = $r2Service->getGlobalStats();
        $dokKbmSchoolsCount = School::where('dok_kbm_enabled', true)->count();
        $totalAllocatedQuotaMb = School::sum('storage_quota_mb');

        $storageStats = [
            'is_online'             => $globalStats['is_online'],
            'dok_kbm_schools_count' => $dokKbmSchoolsCount,
            'total_quota_human'     => ($totalAllocatedQuotaMb >= 1024)
                ? round($totalAllocatedQuotaMb / 1024, 2) . ' GB'
                : $totalAllocatedQuotaMb . ' MB',
            'server_version'        => 'Cloudflare R2',
            'server_url'            => $globalStats['server_url'],
            'bucket'                => $globalStats['bucket'],
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
