@extends('layouts.app')

@section('title', 'Monitoring Absensi Sholat')

@section('content')
<x-page-header 
    title="Monitoring Absensi Sholat" 
    subtitle="Pantau kehadiran sholat berjamaah siswa dan guru secara real-time berdasarkan hisab koordinat" 
    icon="fas fa-mosque">
    <div class="flex items-center gap-2">
        <a href="{{ route('absensi-sholat.rekap') }}" 
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
            <i class="fas fa-calendar-alt text-brand-500"></i> Rekapitulasi Sholat
        </a>
        <a href="{{ route('settings.index') }}" 
           class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
            <i class="fas fa-sliders-h"></i> Atur Waktu & Offset
        </a>
    </div>
</x-page-header>

<!-- Alert Notifikasi -->
@if(session('success'))
    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-success-200 bg-success-50/70 p-4 text-success-800 dark:border-success-800/40 dark:bg-success-950/20 dark:text-success-300">
        <i class="fas fa-check-circle text-lg text-success-500"></i>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
@endif

<!-- Widget Live Hisab Jadwal Sholat Hari Ini -->
@if(isset($prayerSchedule))
<div class="mb-6 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50/70 via-teal-50/40 to-emerald-50/70 p-5 shadow-theme-sm dark:border-emerald-900/40 dark:from-emerald-950/20 dark:via-gray-900 dark:to-emerald-950/20">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
        <div class="flex items-center gap-2.5">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                Jadwal Sholat ({{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }})
            </h4>
        </div>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            Hisab Koordinat: {{ $prayerSchedule['settings']['latitude'] }}, {{ $prayerSchedule['settings']['longitude'] }} (Offset Ihtiyat: +{{ $prayerSchedule['settings']['offsets']['ihtiyat'] }}m)
        </span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        @foreach([
            'subuh' => ['Subuh', 'fas fa-cloud-moon'],
            'terbit' => ['Terbit', 'fas fa-sun'],
            'dhuha' => ['Dhuha', 'fas fa-sun'],
            'dzuhur' => ['Dzuhur / Jumat', 'fas fa-cloud-sun'],
            'ashar' => ['Ashar', 'fas fa-cloud-sun'],
            'maghrib' => ['Maghrib', 'fas fa-sunset'],
            'isya' => ['Isya', 'fas fa-moon']
        ] as $key => [$label, $icon])
            <div class="rounded-xl bg-white dark:bg-gray-800 p-3 text-center border {{ in_array($key, $prayerSchedule['settings']['active_prayers']) ? 'border-emerald-300 dark:border-emerald-700 shadow-xs' : 'border-gray-200 dark:border-gray-700 opacity-60' }}">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 flex items-center justify-center gap-1.5">
                    <i class="{{ $icon }} text-emerald-600 dark:text-emerald-400"></i> {{ $label }}
                </p>
                <p class="text-lg font-bold font-mono text-gray-900 dark:text-white">
                    {{ $prayerSchedule[$key] ?? '--:--' }}
                </p>
                @if(isset($breakdown[$key]) || ($key === 'dzuhur' && isset($breakdown['jumat'])))
                    <span class="inline-block mt-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/20 px-2 py-0.5 rounded-full">
                        {{ ($breakdown[$key] ?? 0) + ($key === 'dzuhur' ? ($breakdown['jumat'] ?? 0) : 0) }} Hadir
                    </span>
                @else
                    <span class="inline-block mt-1 text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700/50 px-2 py-0.5 rounded-full">
                        0 Hadir
                    </span>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

<!-- Statistik Ringkas Hari Ini -->
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Total Absen Sholat</p>
                <h4 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalHadir }}</h4>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <i class="fas fa-users text-xl"></i>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Tepat Waktu / Jamaah</p>
                <h4 class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $totalOntime }}</h4>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <i class="fas fa-check-double text-xl"></i>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Terlambat / Masbuk</p>
                <h4 class="mt-1 text-2xl font-bold text-amber-500 dark:text-amber-400">{{ $totalLate }}</h4>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400">
                <i class="fas fa-user-clock text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Card Filter & Tabel Riwayat Scan -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <!-- Filter Bar -->
    <div class="border-b border-gray-200 p-5 dark:border-gray-800">
        <form action="{{ route('absensi-sholat.index') }}" method="GET" class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="w-full lg:w-44">
                <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                    class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>

            <div class="w-full lg:w-40">
                <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sholat</label>
                <select name="sholat" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Sholat</option>
                    <option value="dhuha" {{ request('sholat') === 'dhuha' ? 'selected' : '' }}>Dhuha</option>
                    <option value="dzuhur" {{ request('sholat') === 'dzuhur' ? 'selected' : '' }}>Dzuhur</option>
                    <option value="jumat" {{ request('sholat') === 'jumat' ? 'selected' : '' }}>Jumat</option>
                    <option value="ashar" {{ request('sholat') === 'ashar' ? 'selected' : '' }}>Ashar</option>
                    <option value="subuh" {{ request('sholat') === 'subuh' ? 'selected' : '' }}>Subuh</option>
                    <option value="maghrib" {{ request('sholat') === 'maghrib' ? 'selected' : '' }}>Maghrib</option>
                    <option value="isya" {{ request('sholat') === 'isya' ? 'selected' : '' }}>Isya</option>
                </select>
            </div>

            <div class="w-full lg:w-36">
                <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Peran</label>
                <select name="person_type" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua</option>
                    <option value="siswa" {{ request('person_type') === 'siswa' ? 'selected' : '' }}>Siswa</option>
                    <option value="guru" {{ request('person_type') === 'guru' ? 'selected' : '' }}>Guru / Pegawai</option>
                </select>
            </div>

            <div class="w-full lg:flex-1">
                <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Nama / NIS / NIP</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau nomor identitas..."
                        class="w-full rounded-xl border border-gray-200 bg-transparent pl-9 pr-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <i class="fas fa-search absolute left-3 top-2.5 text-xs text-gray-400"></i>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                    <i class="fas fa-filter"></i> Filter
                </button>
                @if(request()->hasAny(['sholat', 'person_type', 'search']) || (request('tanggal') && request('tanggal') !== now()->format('Y-m-d')))
                    <a href="{{ route('absensi-sholat.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 transition">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Data -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="border-b border-gray-200 bg-gray-50/70 font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400">
                <tr>
                    <th class="py-3.5 px-4 text-center" width="5%">No</th>
                    <th class="py-3.5 px-4">Waktu Tap</th>
                    <th class="py-3.5 px-4">Nama</th>
                    <th class="py-3.5 px-4">Peran</th>
                    <th class="py-3.5 px-4">Kelas / NIP</th>
                    <th class="py-3.5 px-4">Sholat</th>
                    <th class="py-3.5 px-4">Jadwal Hisab</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4">Scanner</th>
                    <th class="py-3.5 px-4 text-center" width="8%">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                @forelse($attendances as $row)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                        <td class="py-3.5 px-4 text-center font-medium text-gray-400">
                            {{ $loop->iteration + ($attendances->currentPage() - 1) * $attendances->perPage() }}
                        </td>
                        <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-white">
                            {{ substr($row->jam_absen, 0, 5) }}
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">
                            {{ $row->person_name }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($row->person_type === 'guru')
                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-0.5 text-[11px] font-semibold text-purple-700 dark:bg-purple-500/15 dark:text-purple-300">
                                    <i class="fas fa-chalkboard-teacher"></i> Guru
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                                    <i class="fas fa-user-graduate"></i> Siswa
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-gray-600 dark:text-gray-400">
                            @if($row->person_type === 'guru')
                                {{ $row->guru?->nip ?: '-' }}
                            @else
                                {{ $row->student?->kelas?->nama_kelas ?: '-' }}
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-semibold">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">
                                <i class="fas fa-pray text-emerald-600"></i> {{ $row->sholat_label }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 font-mono text-gray-500">
                            {{ $row->waktu_sholat ? substr($row->waktu_sholat, 0, 5) : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($row->status === 'hadir' || $row->status === 'jamaah')
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-[11px] font-bold text-success-700 dark:bg-success-500/15 dark:text-success-400">
                                    <i class="fas fa-check"></i> Tepat Waktu
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-[11px] font-bold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">
                                    <i class="fas fa-clock"></i> Masbuk/Telat
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-gray-500 text-[11px]">
                            {{ $row->device?->name ?: 'Scanner RFID' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <form action="{{ route('absensi-sholat.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data absen ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-gray-400 hover:text-red-500 transition" title="Hapus">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-gray-400 dark:text-gray-500">
                            <i class="fas fa-pray text-4xl mb-3 opacity-30"></i>
                            <p class="text-sm">Belum ada riwayat tap absensi sholat untuk filter ini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($attendances->hasPages())
        <div class="border-t border-gray-200 p-4 dark:border-gray-800">
            {{ $attendances->links() }}
        </div>
    @endif
</div>
@endsection

