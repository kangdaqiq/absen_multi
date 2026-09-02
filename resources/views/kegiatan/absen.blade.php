@extends('layouts.app')

@section('title', 'Absen Kegiatan')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            🎯 Absen Kegiatan
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan Kehadiran Siswa Secara Manual & Real-time</p>
    </div>
</div>

{{-- Notification Toast Container --}}
<div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

{{-- Filter Card --}}
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Pencarian & Kegiatan</h6>
    </div>
    <div class="p-5">
        <form action="{{ route('kegiatan.absen') }}" method="GET" class="flex flex-col xl:flex-row gap-4 items-end">
            <div class="w-full xl:w-1/4">
                <label for="kegiatan_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Kegiatan</label>
                <select name="kegiatan_id" id="kegiatan_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" onchange="this.form.submit()">
                    @forelse($kegiatans as $k)
                        <option value="{{ $k->id }}" {{ $selectedKegiatanId == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kegiatan }} ({{ $k->formatted_hari }})
                        </option>
                    @empty
                        <option value="">-- Belum Ada Kegiatan Aktif --</option>
                    @endforelse
                </select>
            </div>
            
            <div class="w-full xl:w-1/5">
                <label for="tanggal" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" onchange="this.form.submit()">
            </div>

            <div class="w-full xl:w-1/5">
                <label for="kelas_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                <select name="kelas_id" id="kelas_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($allKelas as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full xl:w-1/4 relative">
                <label for="search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Siswa</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Ketik nama siswa..." 
                    oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 600);"
                    class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                <div class="absolute right-3 bottom-2 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <div class="w-full xl:w-auto">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-center font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-filter"></i> Saring
                </button>
            </div>
        </form>
    </div>
</div>

@if(!$kegiatan)
    <div class="rounded-2xl border border-warning-200 bg-warning-50 p-6 dark:border-warning-500/30 dark:bg-warning-500/10 text-center">
        <div class="flex flex-col items-center gap-3 text-warning-600 dark:text-warning-400">
            <i class="fas fa-exclamation-triangle text-5xl"></i>
            <h5 class="text-lg font-bold">Kegiatan Tidak Ditemukan</h5>
            <p class="text-sm max-w-md">Belum ada kegiatan aktif yang dapat dipilih atau kegiatan telah dinonaktifkan. Silakan tambahkan kegiatan aktif baru di menu **Kelola Kegiatan** terlebih dahulu.</p>
            <a href="{{ route('kegiatan.index') }}" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-warning-500 px-5 py-2 text-sm font-medium text-white hover:bg-warning-600 transition">
                <i class="fas fa-cog"></i> Kelola Kegiatan
            </a>
        </div>
    </div>
@else
    {{-- Kegiatan Detail Card --}}
    <div class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h5 class="text-lg font-bold text-brand-800 dark:text-brand-400 flex items-center gap-2">
                <span>{{ $kegiatan->kategori === 'ekskul' ? '⚽' : '🎯' }} {{ $kegiatan->nama_kegiatan }}</span>
                @if($kegiatan->kategori === 'ekskul')
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 font-semibold">
                        <i class="fas fa-futbol mr-1"></i>Ekskul {{ $kegiatan->pembina ? '• ' . $kegiatan->pembina->nama : '' }}
                    </span>
                @endif
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300 font-semibold">
                    {{ $kegiatan->formatted_hari }}
                </span>
                @if($kegiatan->target_type === 'kelas')
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 font-semibold">
                        <i class="fas fa-chalkboard mr-1"></i>{{ $kegiatan->target_scope_label }}
                    </span>
                @elseif($kegiatan->target_type === 'siswa')
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 font-semibold">
                        <i class="fas fa-user-check mr-1"></i>{{ $kegiatan->target_scope_label }}
                    </span>
                @else
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 font-semibold">
                        <i class="fas fa-globe mr-1"></i>Semua Siswa
                    </span>
                @endif
            </h5>
            <p class="text-sm text-brand-600 dark:text-brand-300 mt-1">{{ $kegiatan->deskripsi ?: 'Tidak ada deskripsi kegiatan.' }}</p>
        </div>
        <div class="flex flex-wrap gap-3 font-mono text-sm text-brand-700 dark:text-brand-300">
            <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-brand-100 dark:border-gray-800 px-3 py-1.5 rounded-lg shadow-sm">
                <i class="fas fa-clock text-brand-500"></i>
                Jadwal: {{ $kegiatan->jam_mulai ? \Carbon\Carbon::parse($kegiatan->jam_mulai)->format('H:i') : '-' }} s.d {{ $kegiatan->jam_selesai ? \Carbon\Carbon::parse($kegiatan->jam_selesai)->format('H:i') : '-' }}
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-900 border border-brand-100 dark:border-gray-800 px-3 py-1.5 rounded-lg shadow-sm">
                <i class="fas fa-calendar-alt text-brand-500"></i>
                Tanggal Absen: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}
            </span>
        </div>
    </div>

    {{-- Students Table Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h6 class="font-semibold text-gray-800 dark:text-white/90">Daftar Siswa & Presensi</h6>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $students->total() }} siswa ditemukan</span>
            </div>
            @if($students->count() > 0)
            <form action="{{ route('kegiatan.absen.bulk') }}" method="POST" onsubmit="return confirm('Tandai semua siswa pada halaman/kelas ini sebagai HADIR?')">
                @csrf
                <input type="hidden" name="kegiatan_id" value="{{ $kegiatan->id }}">
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
                <input type="hidden" name="status" value="H">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-4 py-2 text-xs font-semibold text-white hover:bg-success-600 transition shadow-sm">
                    <i class="fas fa-check-double"></i> Tandai Semua Hadir
                </button>
            </form>
            @endif
        </div>

        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                        <th class="px-5 py-4 w-12 text-center">#</th>
                        <th class="px-5 py-4">Nama Lengkap</th>
                        <th class="px-5 py-4">NIS</th>
                        <th class="px-5 py-4">Kelas</th>
                        <th class="px-5 py-4 text-center">Jam Presensi</th>
                        <th class="px-5 py-4 text-center w-80">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($students as $index => $student)
                        @php
                            $hasAttendance = $student->attendance ? true : false;
                            $currentStatus = $hasAttendance ? $student->attendance->status : 'A';
                            $jamMasukStr = ($hasAttendance && $student->attendance->jam_masuk) 
                                ? \Carbon\Carbon::parse($student->attendance->jam_masuk)->format('H:i') 
                                : '-';
                        @endphp
                        <tr x-data="{ 
                            attendance: '{{ $currentStatus }}', 
                            jamMasuk: '{{ $jamMasukStr }}', 
                            loading: false,
                            toggle(targetStatus) {
                                if (this.loading || this.attendance === targetStatus) return;
                                this.loading = true;
                                
                                fetch('{{ route("kegiatan.absen.update") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        kegiatan_id: '{{ $kegiatan->id }}',
                                        student_id: '{{ $student->id }}',
                                        tanggal: '{{ $tanggal }}',
                                        status: targetStatus
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        this.attendance = targetStatus;
                                        this.jamMasuk = (targetStatus !== 'A') ? (data.jam_masuk || '-') : '-';
                                        let labelMap = {'H': 'Hadir', 'I': 'Izin', 'S': 'Sakit', 'A': 'Alpha'};
                                        showToast('success', 'Siswa **' + '{{ $student->nama }}' + '** ditandai **' + labelMap[targetStatus] + '**.');
                                    } else {
                                        showToast('error', 'Gagal mengubah status: ' + data.message);
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    showToast('error', 'Kesalahan koneksi jaringan.');
                                })
                                .finally(() => {
                                    this.loading = false;
                                });
                            }
                        }"
                        class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-5 py-4 text-center text-gray-400 dark:text-gray-600">
                                {{ ($students->currentPage() - 1) * $students->perPage() + $index + 1 }}
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-800 dark:text-white/90">
                                    {{ $student->nama }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-gray-600 dark:text-gray-400 font-mono">
                                {{ $student->nis }}
                            </td>
                            <td class="px-5 py-4 text-gray-600 dark:text-gray-400">
                                {{ $student->kelas->nama_kelas ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span x-text="jamMasuk" 
                                      :class="attendance === 'H' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'text-gray-400'"
                                      class="inline-block rounded px-2.5 py-1 font-mono text-xs font-semibold transition-all">
                                    {{ $jamMasukStr }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 select-none">
                                    {{-- Button Hadir --}}
                                    <button @click="toggle('H')" 
                                            :disabled="loading"
                                            :class="attendance === 'H' 
                                                ? 'bg-success-500 text-white font-bold shadow-sm' 
                                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'"
                                            class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-200">
                                        <i class="fas fa-check-circle" :class="attendance === 'H' ? 'text-white' : 'text-success-500'"></i>
                                        Hadir
                                    </button>
                                    {{-- Button Izin --}}
                                    <button @click="toggle('I')" 
                                            :disabled="loading"
                                            :class="attendance === 'I' 
                                                ? 'bg-warning-500 text-white font-bold shadow-sm' 
                                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'"
                                            class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-200">
                                        <i class="fas fa-info-circle" :class="attendance === 'I' ? 'text-white' : 'text-warning-500'"></i>
                                        Izin
                                    </button>
                                    {{-- Button Sakit --}}
                                    <button @click="toggle('S')" 
                                            :disabled="loading"
                                            :class="attendance === 'S' 
                                                ? 'bg-brand-500 text-white font-bold shadow-sm' 
                                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'"
                                            class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-200">
                                        <i class="fas fa-notes-medical" :class="attendance === 'S' ? 'text-white' : 'text-brand-500'"></i>
                                        Sakit
                                    </button>
                                    {{-- Button Alpha --}}
                                    <button @click="toggle('A')" 
                                            :disabled="loading"
                                            :class="attendance === 'A' 
                                                ? 'bg-error-500 text-white font-bold shadow-sm' 
                                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'"
                                            class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-200">
                                        <i class="fas fa-times-circle" :class="attendance === 'A' ? 'text-white' : 'text-error-500'"></i>
                                        Alpha
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-600">
                                    <i class="fas fa-user-slash text-4xl"></i>
                                    <p class="text-sm">Tidak ada data siswa ditemukan.</p>
                                    <p class="text-xs">Pastikan data kelas dan siswa telah diisi atau ganti filter pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($students->hasPages())
            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                {{ $students->links() }}
            </div>
        @endif
    </div>
@endif

<script>
    function showToast(type, message) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `flex items-center gap-3 rounded-xl border px-4 py-3 shadow-lg transform translate-y-2 opacity-0 transition-all duration-300 ${
            type === 'success' 
                ? 'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-400' 
                : 'border-error-200 bg-error-50 text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400'
        }`;

        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span class="text-xs font-medium">${message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')}</span>
        `;

        container.appendChild(toast);

        // Slide in
        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 10);

        // Fade out & destroy
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3500);
    }
</script>
@endsection
