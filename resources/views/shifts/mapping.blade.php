@extends('layouts.app')

@section('title', 'Plotting Shift Guru')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
                Plotting Shift Guru & Karyawan
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Kelola daftar guru pada masing-masing shift kerja secara praktis.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('shifts.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition shadow-sm">
                <i class="fas fa-clock text-brand-500"></i> Kelola Master Shift
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-500/20 dark:bg-success-500/10">
            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-success-500 text-white mt-0.5">
                <i class="fas fa-check text-xs"></i>
            </div>
            <p class="text-sm font-medium text-success-800 dark:text-success-400">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-error-200 bg-error-50 p-4 dark:border-error-500/20 dark:bg-error-500/10">
            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-error-500 text-white mt-0.5">
                <i class="fas fa-exclamation text-xs"></i>
            </div>
            <p class="text-sm font-medium text-error-800 dark:text-error-400">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Status Overview Counters -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between shadow-theme-sm">
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Guru Terdaftar</span>
                <h4 class="text-xl font-bold text-gray-800 dark:text-white mt-0.5">{{ $totalGurus }} Guru</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-500/15 flex items-center justify-center text-brand-500">
                <i class="fas fa-users text-lg"></i>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between shadow-theme-sm">
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Sudah Memiliki Shift</span>
                <h4 class="text-xl font-bold text-success-600 dark:text-success-400 mt-0.5">{{ $totalPlotted }} Guru</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-success-50 dark:bg-success-500/15 flex items-center justify-center text-success-500">
                <i class="fas fa-user-check text-lg"></i>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between shadow-theme-sm">
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Belum Memiliki Shift</span>
                <h4 class="text-xl font-bold {{ $totalUnplotted > 0 ? 'text-red-500' : 'text-gray-400' }} mt-0.5">{{ $totalUnplotted }} Guru</h4>
            </div>
            <div class="w-10 h-10 rounded-xl {{ $totalUnplotted > 0 ? 'bg-red-50 text-red-500 dark:bg-red-500/15' : 'bg-gray-100 text-gray-400 dark:bg-gray-800' }} flex items-center justify-center">
                <i class="fas fa-user-times text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Main Shifts List Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark mb-6">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
            <h4 class="font-bold text-gray-800 dark:text-white/90 text-sm uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-layer-group text-brand-500"></i> Daftar Shift Kerja
            </h4>
            <span class="text-xs text-gray-400">Total {{ $shifts->count() }} Shift</span>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($shifts as $s)
                @php
                    $assignedGurusList = $s->assignedGurus->map(function($g) {
                        return [
                            'id' => $g->id,
                            'nama' => $g->nama,
                            'nip' => $g->nip ?: '-',
                            'no_wa' => $g->no_wa ?: '-'
                        ];
                    })->values()->toArray();

                    $allGurusList = $allGurus->map(function($g) {
                        return [
                            'id' => $g->id,
                            'nama' => $g->nama,
                            'nip' => $g->nip ?: '-',
                            'no_wa' => $g->no_wa ?: '-'
                        ];
                    })->values()->toArray();
                @endphp
                <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-gray-800 dark:text-white text-base">{{ $s->nama_shift }}</h4>
                            @if($s->kode_shift)
                                <span class="text-[11px] font-mono font-bold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-500/15 px-2 py-0.5 rounded">
                                    {{ $s->kode_shift }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400">
                            Batas Terlambat: <b class="font-mono text-gray-600 dark:text-gray-300">{{ $s->formatted_jam_terlambat }}</b>
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                            <i class="fas fa-calendar-day"></i> {{ $s->formatted_hari_kerja }}
                        </span>

                        <span class="inline-flex items-center gap-1 font-mono text-xs font-bold px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                            <i class="far fa-clock text-brand-500"></i> {{ $s->formatted_jam_masuk }} - {{ $s->formatted_jam_pulang }}
                        </span>

                        <span class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-full {{ count($assignedGurusList) > 0 ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                            <i class="fas fa-users"></i> {{ count($assignedGurusList) }} Guru
                        </span>

                        <button type="button"
                            @click="$dispatch('open-modal', 'modalManageShift-{{ $s->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-sm">
                            <i class="fas fa-users-cog"></i> Atur Guru
                        </button>
                    </div>
                </div>

                {{-- MODAL KELOLA GURU DI SHIFT INI --}}
                <x-ui.modal id="modalManageShift-{{ $s->id }}" :is-open="false" class="max-w-2xl">
                    <div class="p-6" x-data="{
                        allTeachers: {{ json_encode($allGurusList) }},
                        assignedTeachers: {{ json_encode($assignedGurusList) }},
                        selectedToAdd: '',
                        get availableTeachers() {
                            const assignedIds = this.assignedTeachers.map(t => t.id);
                            return this.allTeachers.filter(t => !assignedIds.includes(t.id));
                        },
                        selectTeacher(teacher) {
                            if (!this.assignedTeachers.some(t => t.id === teacher.id)) {
                                this.assignedTeachers.push(teacher);
                            }
                        },
                        removeTeacher(id) {
                            this.assignedTeachers = this.assignedTeachers.filter(t => t.id !== id);
                        },
                        addAllTeachers() {
                            this.assignedTeachers = [...this.allTeachers];
                        },
                        clearAllTeachers() {
                            this.assignedTeachers = [];
                        }
                    }">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                            <div>
                                <h4 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white/90">
                                    Anggota Shift: <span class="text-brand-500">{{ $s->nama_shift }}</span>
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $s->formatted_hari_kerja }} &bull; {{ $s->formatted_jam_masuk }} - {{ $s->formatted_jam_pulang }}
                                </p>
                            </div>
                            <button @click="$dispatch('close-modal', 'modalManageShift-{{ $s->id }}')" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Modal Form -->
                        <form action="{{ route('shifts.mapping.assign') }}" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="shift_id" value="{{ $s->id }}" />

                            <!-- Hidden inputs for assigned teacher IDs -->
                            <template x-for="t in assignedTeachers" :key="t.id">
                                <input type="hidden" name="guru_ids[]" :value="t.id" />
                            </template>

                            <!-- Kolom Search Guru (Punya Scroll Dropdown Sendiri) -->
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 p-3"
                                x-data="{
                                    searchQuery: '',
                                    isOpen: false,
                                    get filteredAvailable() {
                                        if (!this.searchQuery) return this.availableTeachers;
                                        const q = this.searchQuery.toLowerCase();
                                        return this.availableTeachers.filter(t => 
                                            t.nama.toLowerCase().includes(q) || 
                                            (t.nip && t.nip.toLowerCase().includes(q))
                                        );
                                    }
                                }">
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                                    <div class="relative flex-1">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                            <i class="fas fa-search text-xs"></i>
                                        </div>
                                        <input type="text" 
                                            x-model="searchQuery" 
                                            @focus="isOpen = true" 
                                            @input="isOpen = true"
                                            @keydown.escape="isOpen = false"
                                            placeholder="Ketik nama atau NIP guru untuk mencari..."
                                            class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-8 text-xs text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm" />
                                        <button type="button" x-show="searchQuery" @click="searchQuery = ''; isOpen = false" 
                                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 text-xs">
                                            <i class="fas fa-times"></i>
                                        </button>

                                        <!-- Floating Search Suggestions (Scroll Khusus Dropdown Hasil Pencarian) -->
                                        <div x-show="isOpen && filteredAvailable.length > 0" 
                                            x-cloak 
                                            @click.away="isOpen = false"
                                            class="absolute left-0 right-0 top-full mt-1 max-h-52 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900 z-50 divide-y divide-gray-100 dark:divide-gray-800">
                                            
                                            <div class="p-2 bg-gray-50 dark:bg-gray-800/90 flex items-center justify-between text-[11px] text-gray-500 border-b border-gray-100 dark:border-gray-700 sticky top-0 z-10">
                                                <span>Klik guru untuk memasukkan:</span>
                                                <button type="button" @click="filteredAvailable.forEach(t => selectTeacher(t))"
                                                    class="font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                                                    + Pilih Semua (<span x-text="filteredAvailable.length"></span>)
                                                </button>
                                            </div>

                                            <template x-for="at in filteredAvailable" :key="at.id">
                                                <div @click.stop="selectTeacher(at)" 
                                                    class="flex items-center justify-between p-2 hover:bg-brand-50/80 dark:hover:bg-brand-500/15 cursor-pointer transition group">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-4 h-4 rounded border border-gray-300 group-hover:border-brand-500 flex items-center justify-center text-transparent group-hover:text-brand-500 transition text-[9px]">
                                                            <i class="fas fa-plus"></i>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-semibold text-gray-800 dark:text-white" x-text="at.nama"></p>
                                                            <span class="text-[10px] text-gray-400 font-mono" x-text="'NIP: ' + at.nip"></span>
                                                        </div>
                                                    </div>
                                                    <span class="text-[10px] font-bold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-500/15 px-2 py-0.5 rounded group-hover:bg-brand-500 group-hover:text-white transition">
                                                        + Masukkan
                                                    </span>
                                                </div>
                                            </template>
                                        </div>

                                        <div x-show="isOpen && searchQuery && filteredAvailable.length === 0" 
                                            x-cloak 
                                            @click.away="isOpen = false"
                                            class="absolute left-0 right-0 top-full mt-1 p-3 text-center text-xs text-gray-400 rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900 z-50">
                                            Tidak ada guru yang cocok dengan pencarian
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" @click="addAllTeachers()"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition whitespace-nowrap">
                                            <i class="fas fa-users-cog text-brand-500"></i> Tambah Semua
                                        </button>
                                        <button type="button" @click="clearAllTeachers()"
                                            x-show="assignedTeachers.length > 0"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400 transition whitespace-nowrap">
                                            <i class="fas fa-trash-alt"></i> Kosongkan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Guru yang Terdaftar di Shift Ini (SCROLL KHUSUS TABEL) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5 px-1">
                                    <h5 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Guru Terdaftar di Shift Ini (<span x-text="assignedTeachers.length"></span> Guru)
                                    </h5>
                                </div>

                                <template x-if="assignedTeachers.length > 0">
                                    <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-dark shadow-sm">
                                        <table class="w-full table-auto text-left text-xs">
                                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800/95 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-800 z-10">
                                                <tr>
                                                    <th class="px-3.5 py-2.5 w-10 text-center">No</th>
                                                    <th class="px-3.5 py-2.5">Nama Guru</th>
                                                    <th class="px-3.5 py-2.5">NIP</th>
                                                    <th class="px-3.5 py-2.5 text-center w-24">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                <template x-for="(guru, idx) in assignedTeachers" :key="guru.id">
                                                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                                                        <td class="px-3.5 py-2.5 text-center text-gray-400 font-medium" x-text="idx + 1"></td>
                                                        <td class="px-3.5 py-2.5">
                                                            <p class="font-semibold text-gray-800 dark:text-white" x-text="guru.nama"></p>
                                                            <span class="text-[10px] text-gray-400" x-text="guru.no_wa"></span>
                                                        </td>
                                                        <td class="px-3.5 py-2.5 font-mono text-gray-500 dark:text-gray-400" x-text="guru.nip"></td>
                                                        <td class="px-3.5 py-2.5 text-center">
                                                            <button type="button" @click="removeTeacher(guru.id)"
                                                                class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 font-medium transition" title="Keluarkan dari shift">
                                                                <i class="fas fa-trash-alt text-[10px]"></i> Hapus
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>

                                <template x-if="assignedTeachers.length === 0">
                                    <div class="p-6 text-center rounded-xl border border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-dark">
                                        <i class="fas fa-users-slash text-2xl text-gray-300 dark:text-gray-600 mb-2"></i>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada guru yang terdaftar di shift ini.</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">Pilih guru pada kolom pencarian di atas untuk memasukkan guru ke shift ini.</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Modal Action Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button type="button" @click="$dispatch('close-modal', 'modalManageShift-{{ $s->id }}')"
                                    class="rounded-lg border border-gray-200 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="rounded-lg bg-brand-500 px-5 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-sm">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </x-ui.modal>
            @empty
                <div class="p-8 text-center text-gray-400">
                    <i class="far fa-clock text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                    <p class="text-sm">Belum ada master shift kerja yang dibuat.</p>
                    <a href="{{ route('shifts.index') }}" class="mt-2 inline-block text-xs font-bold text-brand-500 hover:underline">
                        + Buat Master Shift Pertama
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section: Guru Belum Memiliki Shift -->
    @if($totalUnplotted > 0)
        <div class="rounded-2xl border border-warning-300/80 bg-warning-50/50 dark:border-warning-500/30 dark:bg-warning-500/5 p-5 shadow-theme-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-warning-500 text-white flex items-center justify-center text-xs">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-white text-sm">
                            {{ $totalUnplotted }} Guru Belum Memiliki Jadwal Shift
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Guru di bawah ini belum bisa melakukan absensi (scan kartu/jari akan ditolak).
                        </p>
                    </div>
                </div>

                <!-- Form Assign Massal Langsung -->
                <form action="{{ route('shifts.mapping.bulk-unassigned') }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    @foreach($unassignedGurus as $uGuru)
                        <input type="hidden" name="guru_ids[]" value="{{ $uGuru->id }}" />
                    @endforeach

                    <select name="shift_id" required
                        class="rounded-lg border border-gray-200 bg-white dark:bg-gray-900 px-3 py-1.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-none cursor-pointer">
                        <option value="">-- Pilih Shift Tujuan --</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">
                                {{ $s->nama_shift }} ({{ $s->formatted_hari_kerja }})
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-warning-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-warning-700 transition shadow-sm whitespace-nowrap">
                        <i class="fas fa-user-plus"></i> Plot Semua ke Shift Ini
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto rounded-xl border border-warning-200 dark:border-warning-500/20 bg-white dark:bg-gray-dark shadow-sm">
                <table class="w-full table-auto text-left text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-800">
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3">Nama Guru</th>
                            <th class="px-4 py-3">NIP</th>
                            <th class="px-4 py-3">No WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($unassignedGurus as $uIdx => $uGuru)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                                <td class="px-4 py-2.5 text-center text-gray-400">{{ $uIdx + 1 }}</td>
                                <td class="px-4 py-2.5 font-semibold text-gray-800 dark:text-white">{{ $uGuru->nama }}</td>
                                <td class="px-4 py-2.5 font-mono text-gray-500 dark:text-gray-400">{{ $uGuru->nip ?: '-' }}</td>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $uGuru->no_wa ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
