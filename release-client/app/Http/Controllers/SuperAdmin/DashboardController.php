<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
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

        return view('super-admin.dashboard', compact(
            'totalSchools',
            'activeSchools',
            'totalAdmins',
            'totalStudents',
            'totalTeachers',
            'recentSchools',
            'schoolsWithStats'
        ));
    }
}
