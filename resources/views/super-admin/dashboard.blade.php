@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
  <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
    <i class="fas fa-crown text-warning-500 mr-2"></i> Super Admin Dashboard
  </h2>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4 2xl:gap-7.5">
    <!-- Total Schools -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-brand-50 rounded-xl dark:bg-brand-500/15 text-brand-500">
        <i class="fas fa-school fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Total Sekolah</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $totalSchools }}</h4>
        </div>
      </div>
    </div>

    <!-- Active Schools -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-success-50 rounded-xl dark:bg-success-500/15 text-success-500">
        <i class="fas fa-check-circle fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Sekolah Aktif</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $activeSchools }}</h4>
        </div>
      </div>
    </div>

    <!-- Total Admins -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-info-50 rounded-xl dark:bg-info-500/15 text-info-500">
        <i class="fas fa-user-shield fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Total Admin</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $totalAdmins }}</h4>
        </div>
      </div>
    </div>

    <!-- Total Students -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-warning-50 rounded-xl dark:bg-warning-500/15 text-warning-500">
        <i class="fas fa-users fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Total Siswa</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $totalStudents }}</h4>
        </div>
      </div>
    </div>
</div>

<div class="mt-4 grid grid-cols-12 gap-4 md:mt-6 md:gap-6 2xl:mt-7.5 2xl:gap-7.5">
    <!-- Schools List -->
    <div class="col-span-12 xl:col-span-8">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 pt-6 pb-2.5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark sm:px-7.5 xl:pb-1">
            <div class="flex justify-between items-center mb-6">
                <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">Daftar Sekolah</h4>
                <a href="{{ route('super-admin.schools.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-2 text-sm font-medium text-brand-500 hover:bg-brand-100 dark:bg-brand-500/15 dark:hover:bg-brand-500/25 transition">
                    <i class="fas fa-list"></i> Lihat Semua
                </a>
            </div>
            
            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-left dark:bg-gray-800/50">
                            <th class="min-w-[150px] px-4 py-4 font-medium text-gray-800 dark:text-white/90">Nama Sekolah</th>
                            <th class="min-w-[100px] px-4 py-4 font-medium text-gray-800 dark:text-white/90">Kode</th>
                            <th class="min-w-[100px] px-4 py-4 font-medium text-gray-800 dark:text-white/90">Siswa</th>
                            <th class="min-w-[100px] px-4 py-4 font-medium text-gray-800 dark:text-white/90">Guru</th>
                            <th class="min-w-[100px] px-4 py-4 font-medium text-gray-800 dark:text-white/90">Status</th>
                            <th class="px-4 py-4 font-medium text-gray-800 dark:text-white/90">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schoolsWithStats as $school)
                            <tr>
                                <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                    <p class="text-gray-800 dark:text-white/90 font-medium">{{ $school->name }}</p>
                                </td>
                                <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                    <p class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $school->code }}</p>
                                </td>
                                <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                    <p class="text-gray-500 dark:text-gray-400">{{ $school->siswa_count }}</p>
                                </td>
                                <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                    <p class="text-gray-500 dark:text-gray-400">{{ $school->guru_count }}</p>
                                </td>
                                <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                    @if($school->is_active)
                                        <p class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Aktif</p>
                                    @else
                                        <p class="inline-flex rounded-full bg-error-50 px-3 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Nonaktif</p>
                                    @endif
                                </td>
                                <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                    <a href="{{ route('super-admin.schools.edit', $school) }}" class="inline-flex items-center justify-center rounded-lg bg-warning-50 px-3 py-2 text-sm font-medium text-warning-600 hover:bg-warning-100 dark:bg-warning-500/15 dark:hover:bg-warning-500/25 transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="border-b border-gray-100 px-4 py-5 dark:border-gray-800 text-center text-gray-500">
                                    Belum ada sekolah
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Global Stats -->
    <div class="col-span-12 xl:col-span-4 space-y-4 md:space-y-6 2xl:space-y-7.5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark sm:p-7.5">
            <h4 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90">Quick Actions</h4>
            
            <div class="flex flex-col gap-3 mb-6">
                <a href="{{ route('super-admin.schools.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-brand-500 px-4 py-3 text-center font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-plus"></i> Tambah Sekolah Baru
                </a>
                <a href="{{ route('super-admin.schools.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-info-500 px-4 py-3 text-center font-medium text-white hover:bg-info-600 transition">
                    <i class="fas fa-school"></i> Kelola Sekolah
                </a>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-800" />
            
            <h5 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Statistik Global</h5>
            <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                    <span class="text-gray-500 dark:text-gray-400">Total Guru</span>
                    <span class="font-bold text-gray-800 dark:text-white/90">{{ $totalTeachers }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                    <span class="text-gray-500 dark:text-gray-400">Total Siswa</span>
                    <span class="font-bold text-gray-800 dark:text-white/90">{{ $totalStudents }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Total Admin</span>
                    <span class="font-bold text-gray-800 dark:text-white/90">{{ $totalAdmins }}</span>
                </div>
            </div>
        </div>

        <!-- Recent Schools -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark sm:p-7.5">
            <h4 class="mb-5 text-xl font-bold text-gray-800 dark:text-white/90">Sekolah Terbaru</h4>
            <div class="space-y-4">
                @forelse($recentSchools as $school)
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h6 class="font-medium text-gray-800 dark:text-white/90 truncate">{{ $school->name }}</h6>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-calendar-alt mr-1"></i> {{ $school->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400">Belum ada sekolah</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection