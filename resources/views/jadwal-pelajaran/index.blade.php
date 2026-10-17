@extends('layouts.app')

@section('title', 'Jadwal Pembelajaran')

@section('content')
    <x-page-header 
        title="Jadwal Pembelajaran" 
        subtitle="Atur jadwal pelajaran kelas, guru pengajar, jam pelajaran, dan ruangan" 
        icon="fas fa-calendar-alt"
        x-data="{}">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('jam-pelajaran.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm text-sm">
                <i class="far fa-clock text-brand-500"></i> Jam Pelajaran
            </a>
            <a href="{{ route('absensi-pelajaran.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm text-sm">
                <i class="fas fa-clipboard-check text-brand-500"></i> Absensi Hari Ini
            </a>
            @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'waka_kurikulum']))
                <button type="button" @click="$dispatch('open-modal', 'modalImportJadwal')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-success-300 bg-success-50 px-3.5 py-2.5 text-center font-medium text-success-700 hover:bg-success-100 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400 transition shadow-sm text-sm cursor-pointer"
                    style="border-color: #a6f4c5; color: #027a48; background-color: #ecfdf3;">
                    <i class="fas fa-file-excel text-success-600 dark:text-success-400" style="color: #12b76a;"></i> Import Excel
                </button>
                <button @click="$dispatch('open-modal', 'modalTambahJadwal')"
                    class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-brand-500 px-4 py-2.5 text-center font-medium text-white hover:bg-brand-600 transition shadow-sm text-sm cursor-pointer">
                    <i class="fas fa-plus"></i> Tambah Jadwal
                </button>
            @endif
        </div>
    </x-page-header>

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-500/20 dark:bg-success-500/10 text-success-800 dark:text-success-400">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/20 dark:bg-warning-500/10 text-warning-800 dark:text-warning-400">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('warning') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-error-200 bg-error-50 p-4 dark:border-error-500/20 dark:bg-error-500/10 text-error-800 dark:text-error-400">
            <i class="fas fa-times-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mb-6 rounded-2xl border border-warning-200 bg-warning-50/70 p-5 dark:border-warning-500/20 dark:bg-warning-500/10 text-gray-800 dark:text-gray-200" x-data="{ showDetails: true }">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2.5 text-warning-800 dark:text-warning-300 font-semibold text-sm">
                    <i class="fas fa-exclamation-triangle text-base text-warning-600"></i>
                    <span>Terdapat {{ count(session('import_errors')) }} baris data jadwal yang dilewati saat impor:</span>
                </div>
                <button type="button" @click="showDetails = !showDetails" class="text-xs text-brand-600 dark:text-brand-400 hover:underline font-medium cursor-pointer">
                    <span x-text="showDetails ? 'Sembunyikan Rincian' : 'Lihat Rincian Baris'"></span>
                </button>
            </div>
            <div x-show="showDetails" class="mt-3 max-h-56 overflow-y-auto rounded-lg border border-warning-200 bg-white dark:border-gray-800 dark:bg-gray-900 text-xs">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-medium">
                        <tr>
                            <th class="px-3 py-2 text-center w-16">Baris</th>
                            <th class="px-3 py-2 w-44">Info Data</th>
                            <th class="px-3 py-2">Alasan Penolakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach(session('import_errors') as $err)
                            <tr>
                                <td class="px-3 py-2 text-center font-bold text-gray-700 dark:text-gray-300">{{ $err['row'] ?? '-' }}</td>
                                <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-200">{{ $err['info'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-error-600 dark:text-error-400">{{ $err['reason'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <i class="fas fa-filter text-xs"></i>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Jadwal Pelajaran</h6>
                </div>
            </div>
            @if(request()->hasAny(['hari', 'kelas_id', 'guru_id', 'mapel_id', 'search']))
                <a href="{{ route('jadwal-pelajaran.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>
        <div class="p-5">
            <form action="{{ route('jadwal-pelajaran.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <!-- Filter Hari -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Hari</label>
                    <div class="relative">
                        <select name="hari" onchange="this.form.submit()"
                            class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ request('hari') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                            <option value="">Semua Hari</option>
                            @foreach($hariList as $h)
                                <option value="{{ $h }}" {{ request('hari') == $h ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <!-- Filter Kelas -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kelas</label>
                    <div class="relative">
                        <select name="kelas_id" onchange="this.form.submit()"
                            class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ request('kelas_id') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                            <option value="">Semua Kelas</option>
                            @foreach($kelases as $k)
                                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <!-- Filter Guru -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Guru Pengajar</label>
                    <div class="relative">
                        <select name="guru_id" onchange="this.form.submit()"
                            class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ request('guru_id') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                            <option value="">Semua Guru</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <!-- Filter Mapel -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Mata Pelajaran</label>
                    <div class="relative">
                        <select name="mapel_id" onchange="this.form.submit()"
                            class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ request('mapel_id') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                            <option value="">Semua Mapel</option>
                            @foreach($mapels as $m)
                                <option value="{{ $m->id }}" {{ request('mapel_id') == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <!-- Cari -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Kata Kunci</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari jadwal..."
                            oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 500);"
                            class="w-full rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-9 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('search') ? 'border-brand-500' : '' }}">
                        <button type="submit" class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-brand-500">
                            <i class="fas fa-search text-xs"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex flex-col sm:flex-row justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800 gap-4">
            <div class="flex items-center gap-3">
                <h4 class="font-semibold text-gray-800 dark:text-white/90">Daftar Jadwal Pelajaran</h4>
                @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'waka_kurikulum']))
                    <button type="button" id="btnBulkDelete"
                        class="hidden rounded-lg bg-error-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-error-600 transition"
                        @click="$dispatch('open-modal', 'modalBulkDelete')">
                        <i class="fas fa-trash-alt mr-1"></i> Hapus Terpilih
                    </button>
                @endif
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Total: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $jadwals->total() }}</span> Jadwal
            </p>
        </div>

        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                        @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'waka_kurikulum']))
                            <th class="px-4 py-4 xl:pl-6 w-12 text-center">
                                <input type="checkbox" id="checkAll" class="rounded border-gray-300 text-brand-600 focus:border-brand-500 focus:ring-brand-500 cursor-pointer">
                            </th>
                        @endif
                        <th class="px-4 py-4 w-12">No</th>
                        <th class="px-4 py-4">Hari & Jam</th>
                        <th class="px-4 py-4">Kelas</th>
                        <th class="px-4 py-4">Mata Pelajaran</th>
                        <th class="px-4 py-4">Guru Pengajar</th>
                        <th class="px-4 py-4">Ruangan</th>
                        <th class="px-4 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse ($jadwals as $j)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'waka_kurikulum']))
                                <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 xl:pl-6 text-center">
                                    <input type="checkbox" name="selected_ids[]" value="{{ $j->id }}" class="checkItem rounded border-gray-300 text-brand-600 focus:border-brand-500 focus:ring-brand-500 cursor-pointer">
                                </td>
                            @endif
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                                <p class="text-gray-500 dark:text-gray-400">{{ $loop->iteration + $jadwals->firstItem() - 1 }}</p>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                            {{ $j->hari }}
                                        </span>
                                        @if($j->jam_ke)
                                            <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                Jam ke-{{ $j->jam_ke }}
                                            </span>
                                        @endif
                                        @if($j->jamPelajaran)
                                            <span class="inline-flex rounded-md bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700 dark:bg-purple-500/15 dark:text-purple-300" title="Jam Pelajaran">
                                                {{ $j->jamPelajaran->nama_jam }}
                                                @if($j->jamPelajaranAkhir && $j->jamPelajaranAkhir->id !== $j->jamPelajaran->id)
                                                    s/d {{ $j->jamPelajaranAkhir->nama_jam }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-1">
                                        <i class="far fa-clock text-gray-400 mr-1"></i> {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                    </span>
                                </div>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                                <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                                    {{ $j->kelas?->nama_kelas ?? '-' }}
                                </span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 font-medium text-gray-800 dark:text-white/90">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-book text-brand-500 text-xs"></i>
                                    <span>{{ $j->mapel?->nama_mapel ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                                <div class="flex items-center gap-2">
                                    <div class="h-7 w-7 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 shrink-0">
                                        {{ substr($j->guru?->nama ?? 'G', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-white/90 leading-tight">{{ $j->guru?->nama ?? '-' }}</p>
                                        @if($j->guru?->nip)
                                            <span class="text-[11px] text-gray-400">NIP: {{ $j->guru->nip }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                                <span class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $j->ruangan ?: '-' }}
                                </span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                                <div class="flex items-center justify-center gap-1.5" x-data="{}">
                                    <!-- Input Absen Langsung -->
                                    <a href="{{ route('absensi-pelajaran.presensi', $j->id) }}"
                                        class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2.5 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 transition"
                                        title="Absen Kelas Ini">
                                        <i class="fas fa-clipboard-check"></i> Absen
                                    </a>

                                    @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'waka_kurikulum']))
                                        <!-- Edit -->
                                        <button class="btnEditJadwal text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-1.5 rounded-lg transition"
                                            data-id="{{ $j->id }}"
                                            data-jam_pelajaran_id="{{ $j->jam_pelajaran_id }}"
                                            data-jam_pelajaran_id_akhir="{{ $j->jam_pelajaran_id_akhir }}"
                                            data-guru_id="{{ $j->guru_id }}"
                                            data-kelas_id="{{ $j->kelas_id }}"
                                            data-mapel_id="{{ $j->mapel_id }}"
                                            data-hari="{{ $j->hari }}"
                                            data-jam_ke="{{ $j->jam_ke }}"
                                            data-jam_mulai="{{ substr($j->jam_mulai, 0, 5) }}"
                                            data-jam_selesai="{{ substr($j->jam_selesai, 0, 5) }}"
                                            data-ruangan="{{ $j->ruangan }}"
                                            data-keterangan="{{ $j->keterangan }}"
                                            @click="$dispatch('open-modal', 'modalEditJadwal')" title="Edit Jadwal">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Delete -->
                                        <button class="btnHapusJadwal text-error-500 hover:text-error-700 hover:bg-error-50 p-1.5 rounded-lg transition"
                                            data-id="{{ $j->id }}"
                                            data-info="{{ ($j->mapel?->nama_mapel ?? '') . ' (' . ($j->kelas?->nama_kelas ?? '') . ' - ' . $j->hari . ')' }}"
                                            @click="$dispatch('open-modal', 'modalHapusJadwal')" title="Hapus Jadwal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border-b border-gray-100 px-4 py-8 dark:border-gray-800 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada jadwal pembelajaran ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $jadwals->links('vendor.pagination.tailwind') }}
        </div>
    </div>

    <!-- ========================= MODALS ========================= -->

    <!-- Modal Tambah Jadwal -->
    <x-ui.modal id="modalTambahJadwal" :is-open="false" class="max-w-2xl sm:max-w-2xl" x-data="{}">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Tambah Jadwal Pelajaran</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('jadwal-pelajaran.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                        <!-- Multi-Kelas Dropdown Repeater -->
                        <div x-data="kelasRepeater()" class="space-y-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Kelas <span class="text-error-500">*</span>
                                </label>
                                <a href="{{ route('kelas.index') }}" target="_blank" class="text-xs text-brand-600 hover:underline dark:text-brand-400 font-medium">
                                    + Kelola Kelas
                                </a>
                            </div>

                            <!-- List Dropdown Kelas -->
                            <div class="space-y-2">
                                <template x-for="(item, index) in rows" :key="item.uid">
                                    <div class="flex items-center gap-2">
                                        <select name="kelas_ids[]" x-model="item.kelas_id" @change="onKelasChange()" required
                                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                            <option value="">-- Pilih Kelas --</option>
                                            @foreach($kelases as $k)
                                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" x-show="rows.length > 1" @click="removeRow(index)"
                                            class="h-9 w-9 shrink-0 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 hover:text-red-700 dark:border-red-900/50 dark:hover:bg-red-900/20 transition cursor-pointer"
                                            title="Hapus kelas ini">
                                            <i class="fas fa-times text-sm"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <!-- Tombol Tambah Kelas -->
                            <div class="pt-0.5">
                                <button type="button" @click="addRow()"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 hover:underline cursor-pointer">
                                    <i class="fas fa-plus-circle text-xs"></i>
                                    <span>Tambah Kelas</span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Mata Pelajaran</label>
                            </div>
                            <select name="mapel_id" id="tambah_mapel_id" required class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                                @foreach($mapels as $m)
                                    <option value="{{ $m->id }}" data-kelas-ids="{{ json_encode($m->kelases->pluck('id')) }}">
                                        {{ $m->nama_mapel }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1" id="tambah_mapel_info">Pilih kelas di sebelah kiri untuk melihat mata pelajaran yang tersedia.</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Guru Pengajar</label>
                        <select name="guru_id" required class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <option value="">-- Pilih Guru Pengajar --</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }} ({{ $g->nip ?: 'Non-NIP' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sesi Jam Pelajaran & Hari -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Hari <span class="text-error-500">*</span></label>
                            <select name="hari" id="tambah_hari" required class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                @foreach($hariList as $h)
                                    <option value="{{ $h }}">{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Jam Mulai <span class="text-error-500">*</span>
                                </label>
                                <a href="{{ route('jam-pelajaran.index') }}" target="_blank" class="text-xs text-brand-600 hover:underline dark:text-brand-400 font-medium">
                                    + Kelola Jam
                                </a>
                            </div>
                            <select name="jam_pelajaran_id" id="tambah_jam_pelajaran_id" required
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <option value="">-- Pilih Jam Mulai --</option>
                                @foreach($jamPelajarans as $jp)
                                    <option value="{{ $jp->id }}" data-hari="{{ $jp->hari }}" data-jam-ke="{{ $jp->jam_ke }}" data-urutan="{{ $jp->urutan }}" data-nama="{{ $jp->nama_jam }}" data-mulai="{{ substr($jp->jam_mulai, 0, 5) }}" data-selesai="{{ substr($jp->jam_selesai, 0, 5) }}">
                                        {{ $jp->label }} {{ $jp->hari ? '(' . $jp->hari . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Gandeng Jam Pelajaran (Opsi Jam Akhir) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="hidden sm:block"></div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                    <span>Sampai Jam Ke</span>
                                </label>
                            </div>
                            <select name="jam_pelajaran_id_akhir" id="tambah_jam_pelajaran_id_akhir"
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <option value="">-- 1 Jam Pelajaran --</option>
                                @foreach($jamPelajarans as $jp)
                                    <option value="{{ $jp->id }}" data-hari="{{ $jp->hari }}" data-jam-ke="{{ $jp->jam_ke }}" data-urutan="{{ $jp->urutan }}" data-nama="{{ $jp->nama_jam }}" data-mulai="{{ substr($jp->jam_mulai, 0, 5) }}" data-selesai="{{ substr($jp->jam_selesai, 0, 5) }}">
                                         {{ $jp->nama_jam }} (Selesai {{ substr($jp->jam_selesai, 0, 5) }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="tambah_jam_preview" class="hidden mt-2 p-2.5 rounded-lg bg-brand-50/80 border border-brand-200 text-xs text-brand-700 dark:bg-brand-500/10 dark:border-brand-500/20 dark:text-brand-300 font-semibold flex items-center gap-1.5">
                                <i class="fas fa-layer-group text-brand-500"></i>
                                <span class="preview-text"></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ruangan (Opsional)</label>
                            <input type="text" name="ruangan" placeholder="Contoh: Lab Komputer, Kelas 10-A" class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (Opsional)</label>
                            <input type="text" name="keterangan" placeholder="Keterangan tambahan" class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 text-sm">Batal</button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600 text-sm font-medium">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Edit Jadwal -->
    <x-ui.modal id="modalEditJadwal" :is-open="false" class="max-w-2xl sm:max-w-2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Jadwal Pelajaran</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="#" method="POST" id="formEditJadwal">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                        <!-- Multi-Kelas Dropdown Repeater untuk Edit -->
                        <div x-data="kelasRepeaterEdit()" class="space-y-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Kelas <span class="text-error-500">*</span>
                                </label>
                                <a href="{{ route('kelas.index') }}" target="_blank" class="text-xs text-brand-600 hover:underline dark:text-brand-400 font-medium">
                                    + Kelola Kelas
                                </a>
                            </div>

                            <!-- List Dropdown Kelas -->
                            <div class="space-y-2">
                                <template x-for="(item, index) in rows" :key="item.uid">
                                    <div class="flex items-center gap-2">
                                        <select name="kelas_ids[]" x-model="item.kelas_id" @change="onKelasChange()" required
                                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                            <option value="">-- Pilih Kelas --</option>
                                            @foreach($kelases as $k)
                                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" x-show="rows.length > 1" @click="removeRow(index)"
                                            class="h-9 w-9 shrink-0 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 hover:text-red-700 dark:border-red-900/50 dark:hover:bg-red-900/20 transition cursor-pointer"
                                            title="Hapus kelas ini">
                                            <i class="fas fa-times text-sm"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <!-- Tombol Tambah Kelas -->
                            <div class="pt-0.5">
                                <button type="button" @click="addRow()"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 hover:underline cursor-pointer">
                                    <i class="fas fa-plus-circle text-xs"></i>
                                    <span>Tambah Kelas</span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Mata Pelajaran</label>
                                <a href="{{ route('mapel.index') }}" target="_blank" class="text-xs text-brand-600 hover:underline dark:text-brand-400 font-medium">
                                    + Kelola Mapel
                                </a>
                            </div>
                            <select name="mapel_id" id="edit_mapel_id" required class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach($mapels as $m)
                                    <option value="{{ $m->id }}" data-kelas-ids="{{ json_encode($m->kelases->pluck('id')) }}">
                                        {{ $m->nama_mapel }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1" id="edit_mapel_info"></p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Guru Pengajar</label>
                        <select name="guru_id" id="edit_guru_id" required class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }} ({{ $g->nip ?: 'Non-NIP' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sesi Jam Pelajaran & Hari -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Hari <span class="text-error-500">*</span></label>
                            <select name="hari" id="edit_hari" required class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                @foreach($hariList as $h)
                                    <option value="{{ $h }}">{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Jam Mulai <span class="text-error-500">*</span>
                                </label>
                                <a href="{{ route('jam-pelajaran.index') }}" target="_blank" class="text-xs text-brand-600 hover:underline dark:text-brand-400 font-medium">
                                    + Kelola Jam
                                </a>
                            </div>
                            <select name="jam_pelajaran_id" id="edit_jam_pelajaran_id" required
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <option value="">-- Pilih Jam Mulai --</option>
                                @foreach($jamPelajarans as $jp)
                                    <option value="{{ $jp->id }}" data-hari="{{ $jp->hari }}" data-jam-ke="{{ $jp->jam_ke }}" data-urutan="{{ $jp->urutan }}" data-nama="{{ $jp->nama_jam }}" data-mulai="{{ substr($jp->jam_mulai, 0, 5) }}" data-selesai="{{ substr($jp->jam_selesai, 0, 5) }}">
                                        {{ $jp->label }} {{ $jp->hari ? '(' . $jp->hari . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Gandeng Jam Pelajaran (Opsi Jam Akhir) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="hidden sm:block"></div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                    <span>Sampai Jam Ke</span>
                                    </label>
                            </div>
                            <select name="jam_pelajaran_id_akhir" id="edit_jam_pelajaran_id_akhir"
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <option value="">-- 1 Jam Pelajaran --</option>
                                @foreach($jamPelajarans as $jp)
                                    <option value="{{ $jp->id }}" data-hari="{{ $jp->hari }}" data-jam-ke="{{ $jp->jam_ke }}" data-urutan="{{ $jp->urutan }}" data-nama="{{ $jp->nama_jam }}" data-mulai="{{ substr($jp->jam_mulai, 0, 5) }}" data-selesai="{{ substr($jp->jam_selesai, 0, 5) }}">
                                        Sampai {{ $jp->nama_jam }} (Selesai {{ substr($jp->jam_selesai, 0, 5) }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="edit_jam_preview" class="hidden mt-2 p-2.5 rounded-lg bg-brand-50/80 border border-brand-200 text-xs text-brand-700 dark:bg-brand-500/10 dark:border-brand-500/20 dark:text-brand-300 font-semibold flex items-center gap-1.5">
                                <i class="fas fa-layer-group text-brand-500"></i>
                                <span class="preview-text"></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ruangan (Opsional)</label>
                            <input type="text" name="ruangan" id="edit_ruangan" class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (Opsional)</label>
                            <input type="text" name="keterangan" id="edit_keterangan" class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 text-sm">Batal</button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600 text-sm font-medium">Update Jadwal</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Hapus Jadwal -->
    <x-ui.modal id="modalHapusJadwal" :is-open="false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-error-500">Hapus Jadwal Pelajaran</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="#" method="POST" id="formHapusJadwal">
                @csrf
                @method('DELETE')
                <p class="text-gray-700 dark:text-gray-300 mb-6">Yakin ingin menghapus jadwal: <strong id="hapus_info_jadwal" class="text-gray-900 dark:text-white"></strong>?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 text-sm">Batal</button>
                    <button type="submit" class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600 text-sm font-medium">Hapus</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Bulk Delete -->
    <x-ui.modal id="modalBulkDelete" :is-open="false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-error-500">Hapus Masal Jadwal</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('jadwal-pelajaran.bulk-destroy') }}" method="POST" id="formBulkDelete">
                @csrf
                @method('DELETE')
                <div id="bulkDeleteInputs"></div>
                <p class="text-gray-700 dark:text-gray-300 mb-6">Yakin ingin menghapus <strong id="bulkDeleteCount" class="text-gray-900 dark:text-white">0</strong> jadwal yang dipilih?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 text-sm">Batal</button>
                    <button type="submit" class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600 text-sm font-medium">Hapus Semua Terpilih</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Import Jadwal dari Excel -->
    <x-ui.modal id="modalImportJadwal" :is-open="false" class="max-w-xl sm:max-w-xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-lg bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400 flex items-center justify-center text-lg" style="background-color: #ecfdf3; color: #12b76a;">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Import Jadwal dari Excel</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Unggah file jadwal pelajaran format Excel (.xlsx)</p>
                    </div>
                </div>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Card Unduh Template dengan Dropdown -->
            <div class="mb-5 rounded-xl border border-success-200 bg-success-50/70 p-4 dark:border-success-500/20 dark:bg-success-500/10" style="border-color: #a6f4c5; background-color: #f6fef9;">
                <div class="flex items-center gap-2 text-xs font-bold text-success-700 dark:text-success-400 mb-1.5" style="color: #027a48;">
                    <i class="fas fa-magic"></i>
                    <span>Download Template Excel</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed mb-3">
                    Unduh template Excel, pastikan Jam Pelajaran, dan Mata Pelajaran sudah di Input.
                </p>
                <div>
                    <a href="{{ route('jadwal-pelajaran.download-template') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-4 py-2.5 text-xs font-bold text-white hover:bg-success-600 transition shadow-sm cursor-pointer"
                        style="background-color: #12b76a; color: #ffffff !important; text-decoration: none;">
                        <i class="fas fa-download text-sm" style="color: #ffffff;"></i>
                        <span style="color: #ffffff;">Unduh Template Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <form action="{{ route('jadwal-pelajaran.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <!-- File Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Pilih File Excel (.xlsx, .xls, .csv) <span class="text-error-500">*</span>
                            </label>
                        </div>
                        <input type="file" name="fileExcel" accept=".xlsx,.xls,.csv" required
                            class="w-full text-xs text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 dark:file:text-brand-300 border border-gray-200 dark:border-gray-800 rounded-lg p-1.5 bg-gray-50/50 dark:bg-gray-900">
                    </div>

                    <!-- Mode Import -->
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5 dark:border-gray-800 dark:bg-gray-800/40">
                        <label class="block mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                            Opsi Mode Impor:
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="radio" name="mode" value="append" checked class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Tambahkan ke Jadwal yang Ada (Append)</span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">Jadwal baru akan ditambahkan tanpa menghapus jadwal yang sudah ada.</span>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="radio" name="mode" value="replace" class="mt-0.5 text-error-600 focus:ring-error-500">
                                <div>
                                    <span class="block text-xs font-semibold text-error-600 dark:text-error-400">Ganti Seluruh Jadwal (Replace All)</span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">Menghapus seluruh jadwal lama sekolah saat ini dan menggantinya dengan isi file Excel.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 text-xs font-medium cursor-pointer">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-success-500 px-4 py-2 text-white hover:bg-success-600 text-xs font-semibold transition cursor-pointer"
                        style="background-color: #12b76a; color: #ffffff !important;">
                        <i class="fas fa-file-upload"></i> Upload & Proses Import
                    </button>
                </div>
            </form>
        </div>
    </x-ui.modal>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        // Alpine component untuk repeater dropdown kelas pada modal Tambah
        window.kelasRepeater = function () {
            return {
                rows: [{ uid: Date.now(), kelas_id: '' }],
                uidCounter: 1,

                addRow: function () {
                    this.rows.push({ uid: Date.now() + (this.uidCounter++), kelas_id: '' });
                },

                removeRow: function (index) {
                    if (this.rows.length > 1) {
                        this.rows.splice(index, 1);
                        this.onKelasChange();
                    }
                },

                onKelasChange: function () {
                    var selectedIds = this.rows
                        .map(function (r) { return r.kelas_id; })
                        .filter(function (id) { return id !== '' && id !== null && id !== undefined; });

                    if (typeof window.filterMapelByKelasMulti === 'function') {
                        window.filterMapelByKelasMulti(selectedIds, '#tambah_mapel_id', '#tambah_mapel_info');
                    }
                }
            };
        };

        // Alpine component untuk repeater dropdown kelas pada modal Edit
        window.kelasRepeaterEdit = function () {
            return {
                rows: [{ uid: Date.now(), kelas_id: '' }],
                uidCounter: 1,

                init: function () {
                    var self = this;
                    window.addEventListener('set-edit-kelas', function (e) {
                        var detail = e.detail || {};
                        var kelasIds = detail.kelasIds || [];
                        if (kelasIds.length === 0) {
                            self.rows = [{ uid: Date.now() + (self.uidCounter++), kelas_id: '' }];
                        } else {
                            self.rows = kelasIds.map(function (id) {
                                return { uid: Date.now() + (self.uidCounter++), kelas_id: String(id) };
                            });
                        }

                        // Filter mapel saat edit dibuka
                        setTimeout(function () {
                            self.onKelasChange(detail.preselectedMapelId);
                        }, 50);
                    });
                },

                addRow: function () {
                    this.rows.push({ uid: Date.now() + (this.uidCounter++), kelas_id: '' });
                },

                removeRow: function (index) {
                    if (this.rows.length > 1) {
                        this.rows.splice(index, 1);
                        this.onKelasChange();
                    }
                },

                onKelasChange: function (preselectedMapelId) {
                    var selectedIds = this.rows
                        .map(function (r) { return r.kelas_id; })
                        .filter(function (id) { return id !== '' && id !== null && id !== undefined; });

                    if (typeof window.filterMapelByKelasMulti === 'function') {
                        window.filterMapelByKelasMulti(selectedIds, '#edit_mapel_id', '#edit_mapel_info', preselectedMapelId);
                    }
                }
            };
        };

        // Filter Mapel by Multiple Selected Kelas
        window.filterMapelByKelasMulti = function (selectedKelasIds, mapelSelectEl, infoEl, preselectedMapelId) {
            var mapelSelect = $(mapelSelectEl);
            var currentVal = preselectedMapelId !== undefined ? preselectedMapelId : mapelSelect.val();
            var availableCount = 0;
            selectedKelasIds = Array.isArray(selectedKelasIds) ? selectedKelasIds.map(Number) : [];

            mapelSelect.find('option').each(function () {
                var opt = $(this);
                var optVal = opt.val();
                if (!optVal) return; // skip placeholder

                var rawKelasIds = opt.data('kelas-ids');
                var kelasIds = Array.isArray(rawKelasIds) ? rawKelasIds.map(Number) : [];

                if (selectedKelasIds.length === 0) {
                    opt.prop('disabled', true).hide();
                } else if (kelasIds.length === 0) {
                    // Mapel berlaku umum untuk semua kelas
                    opt.prop('disabled', false).show();
                    availableCount++;
                } else {
                    // Ada irisan kelas yang dipilih dengan kelas mapel
                    var hasOverlap = selectedKelasIds.some(function (kId) {
                        return kelasIds.indexOf(kId) !== -1;
                    });
                    if (hasOverlap) {
                        opt.prop('disabled', false).show();
                        availableCount++;
                    } else {
                        opt.prop('disabled', true).hide();
                    }
                }
            });

            var placeholder = mapelSelect.find('option[value=""]');
            if (selectedKelasIds.length === 0) {
                placeholder.text('-- Pilih Kelas Terlebih Dahulu --');
                if (infoEl) $(infoEl).text('Pilih kelas di sebelah kiri untuk melihat mata pelajaran yang tersedia.');
            } else if (availableCount === 0) {
                placeholder.text('-- Tidak ada mapel untuk kelas terpilih --');
                if (infoEl) $(infoEl).html('<span class="text-warning-600 dark:text-warning-400"><i class="fas fa-exclamation-triangle"></i> Belum ada mapel ter-mapping untuk kelas terpilih. Atur di menu Mata Pelajaran.</span>');
            } else {
                placeholder.text('-- Pilih Mata Pelajaran (' + availableCount + ' Tersedia) --');
                if (infoEl) $(infoEl).text(availableCount + ' mata pelajaran tersedia untuk ' + selectedKelasIds.length + ' kelas terpilih.');
            }

            if (currentVal && mapelSelect.find('option[value="' + currentVal + '"]:not(:disabled)').length > 0) {
                mapelSelect.val(currentVal);
            } else {
                mapelSelect.val('');
            }
        };

        $(document).ready(function () {
            // Function Filter Mapel by Selected Kelas (Single Select untuk Edit)
            function filterMapelByKelas(kelasSelectEl, mapelSelectEl, infoEl, preselectedMapelId) {
                var selectedKelasId = parseInt($(kelasSelectEl).val());
                var mapelSelect = $(mapelSelectEl);
                var currentVal = preselectedMapelId !== undefined ? preselectedMapelId : mapelSelect.val();
                var availableCount = 0;

                mapelSelect.find('option').each(function () {
                    var opt = $(this);
                    var optVal = opt.val();
                    if (!optVal) return; // skip placeholder

                    var rawKelasIds = opt.data('kelas-ids');
                    var kelasIds = Array.isArray(rawKelasIds) ? rawKelasIds : [];

                    // Jika kelas belum dipilih: sembunyikan semua mapel
                    if (!selectedKelasId) {
                        opt.prop('disabled', true).hide();
                    } else if (kelasIds.length === 0 || kelasIds.indexOf(selectedKelasId) !== -1 || kelasIds.indexOf(selectedKelasId.toString()) !== -1) {
                        // Mapel berlaku untuk semua kelas ATAU ter-mapping ke kelas ini
                        opt.prop('disabled', false).show();
                        availableCount++;
                    } else {
                        // Mapel tidak ter-mapping ke kelas ini
                        opt.prop('disabled', true).hide();
                    }
                });

                var placeholder = mapelSelect.find('option[value=""]');
                if (!selectedKelasId) {
                    placeholder.text('-- Pilih Kelas Terlebih Dahulu --');
                    if (infoEl) $(infoEl).text('Pilih kelas di sebelah kiri untuk melihat mata pelajaran yang tersedia.');
                } else if (availableCount === 0) {
                    placeholder.text('-- Tidak ada mapel ter-mapping untuk kelas ini --');
                    if (infoEl) $(infoEl).html('<span class="text-warning-600 dark:text-warning-400"><i class="fas fa-exclamation-triangle"></i> Belum ada mapel ter-mapping ke kelas ini. Atur di menu Mata Pelajaran.</span>');
                } else {
                    placeholder.text('-- Pilih Mata Pelajaran (' + availableCount + ' Tersedia) --');
                    if (infoEl) $(infoEl).text(availableCount + ' mata pelajaran ter-mapping untuk kelas ini.');
                }

                // Restore selected value if still valid
                if (currentVal && mapelSelect.find('option[value="' + currentVal + '"]:not(:disabled)').length > 0) {
                    mapelSelect.val(currentVal);
                } else {
                    mapelSelect.val('');
                }
            }

            // Inisialisasi awal filter mapel untuk modal tambah (kosong)
            window.filterMapelByKelasMulti([], '#tambah_mapel_id', '#tambah_mapel_info');

            // Filter Sesi Jam Pelajaran berdasarkan Hari
            function filterJamByHari(hariSelector, jamMulaiSelector, jamAkhirSelector, previewSelector, selectedMulaiToRestore, selectedAkhirToRestore) {
                var selectedHari = $(hariSelector).val() ? $(hariSelector).val().trim().toLowerCase() : '';
                var jamMulaiSelect = $(jamMulaiSelector);
                var currentMulaiVal = selectedMulaiToRestore !== undefined ? selectedMulaiToRestore : jamMulaiSelect.val();
                var availableCount = 0;

                jamMulaiSelect.find('option').each(function () {
                    var opt = $(this);
                    var val = opt.val();
                    if (!val) return; // skip placeholder

                    var optHari = opt.data('hari') ? String(opt.data('hari')).trim() : '';

                    var isMatch = false;
                    if (!optHari || optHari.toLowerCase() === 'semua hari') {
                        isMatch = true;
                    } else if (selectedHari) {
                        var dayList = optHari.split(',').map(function(s) { return s.trim().toLowerCase(); });
                        if (dayList.indexOf(selectedHari) !== -1) {
                            isMatch = true;
                        }
                    }

                    if (isMatch) {
                        opt.prop('disabled', false).prop('hidden', false).show();
                        availableCount++;
                    } else {
                        opt.prop('disabled', true).prop('hidden', true).hide();
                    }
                });

                var placeholder = jamMulaiSelect.find('option[value=""]');
                var hariName = $(hariSelector).val() || 'terpilih';
                if (availableCount === 0) {
                    placeholder.text('-- Tidak ada jam untuk hari ' + hariName + ' --');
                } else {
                    placeholder.text('-- Pilih Jam Mulai (' + availableCount + ' Tersedia) --');
                }

                // Restore selected value if still valid
                if (currentMulaiVal && jamMulaiSelect.find('option[value="' + currentMulaiVal + '"]:not(:disabled)').length > 0) {
                    jamMulaiSelect.val(currentMulaiVal);
                } else {
                    jamMulaiSelect.val('');
                }

                // Perbarui opsi jam gandeng setelah filter hari
                updateJamAkhirOptions(hariSelector, jamMulaiSelector, jamAkhirSelector, previewSelector, selectedAkhirToRestore);
            }

            // Fungsi untuk update opsi sesi jam akhir (Gandeng Jam Pelajaran)
            function updateJamAkhirOptions(hariSelector, jamMulaiSelector, jamAkhirSelector, previewSelector, selectedAkhirToRestore) {
                var jamMulaiSelect = $(jamMulaiSelector);
                var jamAkhirSelect = $(jamAkhirSelector);
                var previewBox = $(previewSelector);
                var selectedMulaiOpt = jamMulaiSelect.find('option:selected');
                var mulaiVal = jamMulaiSelect.val();

                if (!mulaiVal) {
                    jamAkhirSelect.prop('disabled', true).val('');
                    previewBox.addClass('hidden');
                    return;
                }

                jamAkhirSelect.prop('disabled', false);
                var selectedHari = $(hariSelector).val() ? $(hariSelector).val().trim().toLowerCase() : '';
                var startUrutan = parseInt(selectedMulaiOpt.data('urutan')) || 0;
                var startJamKe = selectedMulaiOpt.data('jam-ke') || selectedMulaiOpt.data('nama');
                var startMulai = selectedMulaiOpt.data('mulai') || '';
                var startSelesai = selectedMulaiOpt.data('selesai') || '';
                var startNama = selectedMulaiOpt.data('nama') || '';

                var currentAkhirVal = selectedAkhirToRestore !== undefined ? selectedAkhirToRestore : jamAkhirSelect.val();
                var availableAkhirCount = 0;

                jamAkhirSelect.find('option').each(function () {
                    var opt = $(this);
                    var val = opt.val();
                    if (!val) {
                        opt.text('-- 1 Jam Pelajaran (' + startNama + ': ' + startMulai + ' - ' + startSelesai + ') --');
                        return;
                    }

                    var optHari = opt.data('hari') ? String(opt.data('hari')).trim() : '';
                    var optUrutan = parseInt(opt.data('urutan')) || 0;

                    // Cek kesesuaian hari
                    var isDayMatch = false;
                    if (!optHari || optHari.toLowerCase() === 'semua hari') {
                        isDayMatch = true;
                    } else if (selectedHari) {
                        var dayList = optHari.split(',').map(function(s) { return s.trim().toLowerCase(); });
                        if (dayList.indexOf(selectedHari) !== -1) {
                            isDayMatch = true;
                        }
                    }

                    // Hanya tampilkan sesi jam SETELAH sesi mulai (urutan > startUrutan)
                    if (isDayMatch && optUrutan > startUrutan) {
                        var gapCount = optUrutan - startUrutan + 1;
                        var optNama = opt.data('nama') || '';
                        var optJamKe = opt.data('jam-ke') ? ' [Jam ke ' + opt.data('jam-ke') + ']' : '';
                        var optSelesai = opt.data('selesai') || '';
                        opt.text('Sampai ' + optNama + optJamKe + ' (Selesai ' + optSelesai + ') • ' + gapCount + ' Jam Pelajaran');
                        opt.prop('disabled', false).prop('hidden', false).show();
                        availableAkhirCount++;
                    } else {
                        opt.prop('disabled', true).prop('hidden', true).hide();
                    }
                });

                // Restore selected value jika valid
                if (currentAkhirVal && jamAkhirSelect.find('option[value="' + currentAkhirVal + '"]:not(:disabled)').length > 0) {
                    jamAkhirSelect.val(currentAkhirVal);
                } else {
                    jamAkhirSelect.val('');
                }

                // Render Preview
                renderJamGandengPreview(jamMulaiSelect, jamAkhirSelect, previewBox);
            }

            // Fungsi render preview info jam
            function renderJamGandengPreview(jamMulaiSelect, jamAkhirSelect, previewBox) {
                var mulaiOpt = jamMulaiSelect.find('option:selected');
                var akhirOpt = jamAkhirSelect.find('option:selected');
                var mulaiVal = jamMulaiSelect.val();
                var akhirVal = jamAkhirSelect.val();

                if (!mulaiVal) {
                    previewBox.addClass('hidden');
                    return;
                }

                var startNama = mulaiOpt.data('nama') || '';
                var startJamKe = mulaiOpt.data('jam-ke') ? ' [Jam ke ' + mulaiOpt.data('jam-ke') + ']' : '';
                var startLabel = startNama + startJamKe;
                var startMulai = mulaiOpt.data('mulai') || '';
                var startSelesai = mulaiOpt.data('selesai') || '';
                var startUrutan = parseInt(mulaiOpt.data('urutan')) || 0;

                if (!akhirVal || akhirVal == mulaiVal) {
                    previewBox.removeClass('hidden');
                    previewBox.find('.preview-text').html('<strong>1 Jam Pelajaran:</strong> ' + startLabel + ' (' + startMulai + ' - ' + startSelesai + ')');
                } else {
                    var endNama = akhirOpt.data('nama') || '';
                    var endJamKe = akhirOpt.data('jam-ke') ? ' [Jam ke ' + akhirOpt.data('jam-ke') + ']' : '';
                    var endLabel = endNama + endJamKe;
                    var endSelesai = akhirOpt.data('selesai') || '';
                    var endUrutan = parseInt(akhirOpt.data('urutan')) || 0;
                    var totalJam = (endUrutan > startUrutan) ? (endUrutan - startUrutan + 1) : 2;

                    previewBox.removeClass('hidden');
                    previewBox.find('.preview-text').html('<strong> ' + totalJam + ' Jam Pelajaran:</strong> ' + startLabel + ' s/d ' + endLabel + ' <span class="ml-1 text-gray-500 font-normal">(' + startMulai + ' - ' + endSelesai + ')</span>');
                }
            }

            // Trigger on Tambah Modal
            $('#tambah_hari').on('change', function () {
                filterJamByHari('#tambah_hari', '#tambah_jam_pelajaran_id', '#tambah_jam_pelajaran_id_akhir', '#tambah_jam_preview');
            });

            $('#tambah_jam_pelajaran_id').on('change', function () {
                updateJamAkhirOptions('#tambah_hari', '#tambah_jam_pelajaran_id', '#tambah_jam_pelajaran_id_akhir', '#tambah_jam_preview');
            });

            $('#tambah_jam_pelajaran_id_akhir').on('change', function () {
                renderJamGandengPreview($('#tambah_jam_pelajaran_id'), $('#tambah_jam_pelajaran_id_akhir'), $('#tambah_jam_preview'));
            });

            // Initial Tambah Modal filter
            filterJamByHari('#tambah_hari', '#tambah_jam_pelajaran_id', '#tambah_jam_pelajaran_id_akhir', '#tambah_jam_preview');

            // Trigger on Edit Modal
            $('#edit_hari').on('change', function () {
                filterJamByHari('#edit_hari', '#edit_jam_pelajaran_id', '#edit_jam_pelajaran_id_akhir', '#edit_jam_preview');
            });

            $('#edit_jam_pelajaran_id').on('change', function () {
                updateJamAkhirOptions('#edit_hari', '#edit_jam_pelajaran_id', '#edit_jam_pelajaran_id_akhir', '#edit_jam_preview');
            });

            $('#edit_jam_pelajaran_id_akhir').on('change', function () {
                renderJamGandengPreview($('#edit_jam_pelajaran_id'), $('#edit_jam_pelajaran_id_akhir'), $('#edit_jam_preview'));
            });

            // Edit Modal Handler
            $('.btnEditJadwal').on('click', function () {
                var d = $(this).data();

                // Set kelas terpilih di Alpine multiSelectKelasEdit
                window.dispatchEvent(new CustomEvent('set-edit-kelas', {
                    detail: {
                        kelasIds: d.kelas_id ? [d.kelas_id] : [],
                        preselectedMapelId: d.mapel_id
                    }
                }));

                $('#edit_guru_id').val(d.guru_id);
                $('#edit_hari').val(d.hari);
                
                // Cari id akhir jika belum tersimpan secara eksplisit (misal data lama)
                var idAkhir = d.jam_pelajaran_id_akhir;
                if (!idAkhir && d.jam_selesai) {
                    var matchAkhir = $('#edit_jam_pelajaran_id_akhir option').filter(function() {
                        return $(this).data('selesai') === d.jam_selesai;
                    });
                    if (matchAkhir.length > 0 && matchAkhir.val() != d.jam_pelajaran_id) {
                        idAkhir = matchAkhir.val();
                    }
                }

                filterJamByHari('#edit_hari', '#edit_jam_pelajaran_id', '#edit_jam_pelajaran_id_akhir', '#edit_jam_preview', d.jam_pelajaran_id, idAkhir);

                $('#edit_ruangan').val(d.ruangan || '');
                $('#edit_keterangan').val(d.keterangan || '');
                $('#formEditJadwal').attr('action', '{{ url('jadwal-pelajaran') }}/' + d.id);
            });

            // Hapus Modal Handler
            $('.btnHapusJadwal').on('click', function () {
                var id = $(this).data('id');
                var info = $(this).data('info');
                $('#hapus_info_jadwal').text(info);
                $('#formHapusJadwal').attr('action', '{{ url('jadwal-pelajaran') }}/' + id);
            });

            // Checkbox Bulk
            $('#checkAll').on('change', function () {
                $('.checkItem').prop('checked', $(this).prop('checked'));
                updateBulkDeleteButton();
            });

            $('.checkItem').on('change', function () {
                updateBulkDeleteButton();
            });

            function updateBulkDeleteButton() {
                var selected = $('.checkItem:checked');
                if (selected.length > 0) {
                    $('#btnBulkDelete').removeClass('hidden');
                } else {
                    $('#btnBulkDelete').addClass('hidden');
                }
            }

            $('#btnBulkDelete').on('click', function () {
                var container = $('#bulkDeleteInputs');
                container.empty();
                var count = 0;
                $('.checkItem:checked').each(function () {
                    container.append('<input type="hidden" name="selected_ids[]" value="' + $(this).val() + '">');
                    count++;
                });
                $('#bulkDeleteCount').text(count);
            });
        });
    </script>
@endpush
