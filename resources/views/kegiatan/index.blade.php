@extends('layouts.app')

@section('title', 'Kelola Kegiatan')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        🎯 Kelola Kegiatan
    </h2>
    <button @click="$dispatch('open-modal', 'modalTambahKegiatan')"
        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition">
        <i class="fas fa-plus"></i> Tambah Kegiatan
    </button>
</div>

@if(session('success'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-400">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('info'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-info-200 bg-info-50 px-4 py-3 text-info-700 dark:border-info-500/30 dark:bg-info-500/15 dark:text-info-400">
        <i class="fas fa-info-circle"></i>
        <span>{{ session('info') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- Kartu info cara kerja --}}
<div class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
    <div class="flex items-start gap-3">
        <div class="mt-0.5 text-brand-500 text-lg"><i class="fas fa-info-circle"></i></div>
        <div>
            <h6 class="font-semibold text-brand-700 dark:text-brand-400 mb-1">Cara Kerja Absensi Kegiatan</h6>
            <ol class="text-sm text-brand-600 dark:text-brand-300 space-y-1 list-decimal list-inside">
                <li>Buat kegiatan dan atur tanggal serta jadwal jam kegiatan (<strong>Jam Mulai</strong> & <strong>Jam Selesai</strong>).</li>
                <li>Atur <strong>Cakupan Peserta</strong> (bisa <em>Semua Siswa</em>, <em>Pilih Kelas</em>, atau <em>Pilih Siswa Khusus</em>).</li>
                <li>Pastikan status kegiatan diatur ke <strong>Aktif</strong>.</li>
                <li>Siswa yang termasuk dalam cakupan dapat scan kartu RFID pada jam kegiatan tersebut untuk otomatis tercatat hadir.</li>
            </ol>
        </div>
    </div>
</div>

{{-- Tabel Kegiatan --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Daftar Kegiatan</h6>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $kegiatans->total() }} kegiatan</span>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 font-medium">
                    <th class="px-5 py-4 text-left">Nama Kegiatan</th>
                    <th class="px-5 py-4 text-left">Jenis</th>
                    <th class="px-5 py-4 text-left">Cakupan Peserta</th>
                    <th class="px-5 py-4 text-left">Tanggal & Frekuensi</th>
                    <th class="px-5 py-4 text-center">Jadwal Jam</th>
                    <th class="px-5 py-4 text-center">Total Hadir</th>
                    <th class="px-5 py-4 text-center">Status Waktu</th>
                    <th class="px-5 py-4 text-center">Aktif</th>
                    <th class="px-5 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kegiatans as $kegiatan)
                    @php $isOngoing = $kegiatan->isScheduledNow(); @endphp
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $kegiatan->nama_kegiatan }}</p>
                            @if($kegiatan->deskripsi)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $kegiatan->deskripsi }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($kegiatan->kategori === 'ekskul')
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5 self-start px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                        <i class="fas fa-futbol"></i> Ekstrakurikuler
                                    </span>
                                    @if($kegiatan->pembina)
                                        <span class="text-[11px] text-gray-600 dark:text-gray-300 font-medium" title="Guru Pembina">
                                            <i class="fas fa-user-tie text-emerald-600 mr-1"></i>{{ $kegiatan->pembina->nama }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                                    <i class="fas fa-school"></i> Kegiatan Sekolah
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($kegiatan->target_type === 'kelas')
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5 self-start px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300">
                                        <i class="fas fa-chalkboard"></i> {{ $kegiatan->target_scope_label }}
                                    </span>
                                    @if($kegiatan->kelas->isNotEmpty())
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400 truncate max-w-[180px]" title="{{ $kegiatan->kelas->pluck('nama_kelas')->implode(', ') }}">
                                            {{ $kegiatan->kelas->pluck('nama_kelas')->take(3)->implode(', ') }}{{ $kegiatan->kelas->count() > 3 ? '...' : '' }}
                                        </span>
                                    @endif
                                </div>
                            @elseif($kegiatan->target_type === 'siswa')
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5 self-start px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                        <i class="fas fa-user-check"></i> {{ $kegiatan->target_scope_label }}
                                    </span>
                                    @if($kegiatan->siswas->isNotEmpty())
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400 truncate max-w-[180px]" title="{{ $kegiatan->siswas->pluck('nama')->implode(', ') }}">
                                            {{ $kegiatan->siswas->pluck('nama')->take(2)->implode(', ') }}{{ $kegiatan->siswas->count() > 2 ? '...' : '' }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                                    <i class="fas fa-globe"></i> Semua Siswa
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            <div class="flex flex-col gap-1">
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ \Carbon\Carbon::parse($kegiatan->tanggal_mulai)->format('d/m/Y') }}</span>
                                <span class="inline-flex items-center self-start px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ $kegiatan->formatted_hari }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center text-gray-600 dark:text-gray-400 font-mono whitespace-nowrap">
                            @if($kegiatan->jam_mulai && $kegiatan->jam_selesai)
                                {{ \Carbon\Carbon::parse($kegiatan->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($kegiatan->jam_selesai)->format('H:i') }}
                            @else
                                <span class="text-gray-400 text-xs italic">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            <a href="{{ route('kegiatan.attendance', $kegiatan->id) }}"
                               class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 hover:bg-success-100 dark:bg-success-500/15 dark:text-success-400 transition">
                                <i class="fas fa-users"></i>
                                {{ $kegiatan->total_hadir ?? 0 }}
                            </a>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($isOngoing)
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-400 animate-pulse">
                                    <span class="h-2 w-2 rounded-full bg-success-500"></span>
                                    Sedang Berjalan
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                    Selesai / Belum Mulai
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($kegiatan->is_active)
                                <span class="inline-flex rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-400">Aktif</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                {{-- Rekap Absensi --}}
                                <a href="{{ route('kegiatan.attendance', $kegiatan->id) }}"
                                    class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 transition"
                                    title="Lihat Rekap Absensi">
                                    <i class="fas fa-list-alt"></i> Rekap
                                </a>

                                {{-- Edit --}}
                                <button
                                    @click="$dispatch('open-modal', 'modalEditKegiatan-{{ $kegiatan->id }}')"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-300 transition"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- Hapus --}}
                                <button
                                    @click="$dispatch('open-modal', 'modalHapusKegiatan-{{ $kegiatan->id }}')"
                                    class="rounded-lg p-2 text-error-500 hover:bg-error-50 hover:text-error-700 dark:hover:bg-error-500/15 transition"
                                    title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Modal Edit Kegiatan --}}
                    <x-ui.modal id="modalEditKegiatan-{{ $kegiatan->id }}" :is-open="false">
                        <div class="p-6 max-h-[90vh] overflow-y-auto">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Kegiatan</h3>
                                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:hover:text-white"><i class="fas fa-times"></i></button>
                            </div>
                            <form action="{{ route('kegiatan.update', $kegiatan->id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kegiatan <span class="text-error-500">*</span></label>
                                        <input type="text" name="nama_kegiatan" value="{{ $kegiatan->nama_kegiatan }}" required
                                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                                        <textarea name="deskripsi" rows="2"
                                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">{{ $kegiatan->deskripsi }}</textarea>
                                    </div>

                                    {{-- Kategori / Jenis Kegiatan --}}
                                    <div x-data="{ kategori: '{{ $kegiatan->kategori ?? 'sekolah' }}' }" class="space-y-3">
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Kegiatan <span class="text-error-500">*</span></label>
                                            <input type="hidden" name="kategori" :value="kategori">
                                            <div class="grid grid-cols-2 gap-3">
                                                <button type="button" @click="kategori = 'sekolah'"
                                                    :class="kategori === 'sekolah' ? 'border-blue-500 bg-blue-50/70 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500 font-semibold shadow-sm' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                                                    class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                                                    <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-blue-600 text-white flex-shrink-0">
                                                        <i class="fas fa-school"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-xs">Kegiatan Sekolah</p>
                                                        <p class="text-[11px] opacity-75">Upacara, Senam, dll</p>
                                                    </div>
                                                </button>

                                                <button type="button" @click="kategori = 'ekskul'"
                                                    :class="kategori === 'ekskul' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500 font-semibold shadow-sm' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                                                    class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                                                    <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-emerald-600 text-white flex-shrink-0">
                                                        <i class="fas fa-futbol"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-xs">Ekstrakurikuler</p>
                                                        <p class="text-[11px] opacity-75">Pramuka, Futsal, dll</p>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Dropdown Guru Pembina (Khusus Ekskul) --}}
                                        <div x-show="kategori === 'ekskul'" x-transition class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 dark:border-emerald-800 dark:bg-emerald-950/20">
                                            <label class="mb-1.5 block text-xs font-semibold text-emerald-900 dark:text-emerald-300">
                                                <i class="fas fa-user-tie text-emerald-600 mr-1.5"></i>Guru Pembina / Penanggung Jawab Ekskul <span class="text-error-500">*</span>
                                            </label>
                                            <select name="pembina_id" :required="kategori === 'ekskul'" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                                <option value="">-- Pilih Guru Pembina --</option>
                                                @foreach($allGurus as $guru)
                                                    <option value="{{ $guru->id }}" {{ $kegiatan->pembina_id == $guru->id ? 'selected' : '' }}>
                                                        {{ $guru->nama }} {{ $guru->nip ? '(NIP: ' . $guru->nip . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1.5 text-[11px] text-emerald-700 dark:text-emerald-400">
                                                <i class="fas fa-paper-plane mr-1"></i>Laporan rekap kehadiran kegiatan ini akan otomatis dikirimkan ke WhatsApp & Telegram Guru Pembina tersebut.
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Cakupan Peserta Kegiatan --}}
                                    <div x-data="{
                                        targetType: '{{ $kegiatan->target_type ?? 'all' }}',
                                        selectedKelas: {{ json_encode($kegiatan->kelas->pluck('id')->toArray()) }},
                                        selectedStudents: {{ json_encode($kegiatan->siswas->pluck('id')->toArray()) }},
                                        searchStudent: '',
                                        filterKelas: '',
                                        allKelasIds: {{ json_encode($allKelas->pluck('id')->toArray()) }}
                                    }" class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                                        <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white">
                                            <i class="fas fa-users-cog text-brand-500 mr-1.5"></i>Cakupan Peserta Kegiatan <span class="text-error-500">*</span>
                                        </label>
                                        <input type="hidden" name="target_type" :value="targetType">

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-3">
                                            <!-- Opsi 1: Semua Siswa -->
                                            <button type="button" @click="targetType = 'all'"
                                                :class="targetType === 'all' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300 dark:border-brand-500 shadow-sm font-semibold' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                                                class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                                                <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-brand-500 text-white flex-shrink-0">
                                                    <i class="fas fa-globe"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800 dark:text-white text-xs">Semua Siswa</p>
                                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Seluruh siswa sekolah</p>
                                                </div>
                                            </button>

                                            <!-- Opsi 2: Pilih Kelas -->
                                            <button type="button" @click="targetType = 'kelas'"
                                                :class="targetType === 'kelas' ? 'border-purple-500 bg-purple-50/70 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500 shadow-sm font-semibold' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                                                class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                                                <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-purple-600 text-white flex-shrink-0">
                                                    <i class="fas fa-chalkboard"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800 dark:text-white text-xs">Pilih Kelas</p>
                                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Hanya kelas tertentu</p>
                                                </div>
                                            </button>

                                            <!-- Opsi 3: Pilih Siswa Tertentu -->
                                            <button type="button" @click="targetType = 'siswa'"
                                                :class="targetType === 'siswa' ? 'border-amber-500 bg-amber-50/70 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500 shadow-sm font-semibold' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                                                class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                                                <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-amber-500 text-white flex-shrink-0">
                                                    <i class="fas fa-user-check"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800 dark:text-white text-xs">Pilih Siswa</p>
                                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Siswa khusus (ekskul, dll)</p>
                                                </div>
                                            </button>
                                        </div>

                                        <!-- Panel Pemilihan Kelas -->
                                        <div x-show="targetType === 'kelas'" x-transition class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                    Pilih Kelas Peserta (<span x-text="selectedKelas.length" class="text-purple-600 font-bold"></span> dipilih):
                                                </span>
                                                <div class="flex items-center gap-2 text-xs">
                                                    <button type="button" @click="selectedKelas = [...allKelasIds]" class="text-purple-600 hover:underline dark:text-purple-400 font-medium">Pilih Semua</button>
                                                    <span class="text-gray-300 dark:text-gray-700">•</span>
                                                    <button type="button" @click="selectedKelas = []" class="text-gray-500 hover:underline dark:text-gray-400 font-medium">Reset</button>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto p-1">
                                                @foreach($allKelas as $kls)
                                                <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 bg-white text-xs text-gray-700 cursor-pointer hover:bg-purple-50/50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-purple-500/10 transition">
                                                    <input type="checkbox" name="kelas_ids[]" :value="{{ $kls->id }}" x-model.number="selectedKelas" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800">
                                                    <span class="truncate font-medium">{{ $kls->nama_kelas }}</span>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Panel Pemilihan Siswa Tertentu -->
                                        <div x-show="targetType === 'siswa'" x-transition class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                    Daftar Siswa Peserta (<span x-text="selectedStudents.length" class="text-amber-600 font-bold"></span> dipilih):
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <input type="text" x-model="searchStudent" placeholder="Cari nama / NIS..."
                                                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs outline-none focus:border-amber-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                                    <select x-model="filterKelas" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs outline-none focus:border-amber-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                                        <option value="">Semua Kelas</option>
                                                        @foreach($allKelas as $kls)
                                                        <option value="{{ $kls->id }}">{{ $kls->nama_kelas }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="space-y-1.5 max-h-56 overflow-y-auto p-1">
                                                @foreach($allStudents as $st)
                                                <label x-show="(searchStudent === '' || '{{ strtolower($st->nama . ' ' . $st->nis) }}'.includes(searchStudent.toLowerCase())) && (filterKelas === '' || filterKelas == '{{ $st->kelas_id }}')"
                                                    class="flex items-center justify-between p-2 rounded-lg border border-gray-200 bg-white text-xs hover:bg-amber-50/40 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-amber-500/10 cursor-pointer transition">
                                                    <div class="flex items-center gap-2.5">
                                                        <input type="checkbox" name="student_ids[]" :value="{{ $st->id }}" x-model.number="selectedStudents" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <div>
                                                            <p class="font-semibold text-gray-800 dark:text-white">{{ $st->nama }}</p>
                                                            <p class="text-[11px] text-gray-400">NIS: {{ $st->nis ?? '-' }}</p>
                                                        </div>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                        {{ $st->kelas->nama_kelas ?? 'Tanpa Kelas' }}
                                                    </span>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="sm:w-1/3">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai <span class="text-error-500">*</span></label>
                                            <input type="date" name="tanggal_mulai" value="{{ $kegiatan->tanggal_mulai->format('Y-m-d') }}" required
                                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                        </div>
                                        <div class="flex-1" x-data="{ 
                                            selected: '{{ $kegiatan->frekuensi ?? 'harian' }}',
                                            days: {{ json_encode(array_map('intval', is_array($kegiatan->hari) ? $kegiatan->hari : [1, 2, 3, 4, 5, 6, 7])) }}
                                        }">
                                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Frekuensi Kegiatan <span class="text-error-500">*</span></label>
                                            <input type="hidden" name="frekuensi" :value="selected">
                                            <div class="flex flex-wrap items-center gap-3 mt-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'sekali'" @change="selected = 'sekali'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Sekali (Insidental)</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'harian'" @change="selected = 'harian'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Harian</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'mingguan'" @change="selected = 'mingguan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Mingguan</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'bulanan'" @change="selected = 'bulanan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Bulanan</span>
                                                </label>
                                            </div>

                                            {{-- Options hari jika harian --}}
                                            <div x-show="selected === 'harian'" x-transition class="mt-3 p-3 rounded-xl border border-gray-200 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-900/50">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Pilih Hari Kegiatan:</span>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <button type="button" @click="days = [1,2,3,4,5,6,7]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Semua Hari</button>
                                                        <span class="text-gray-300 dark:text-gray-700">•</span>
                                                        <button type="button" @click="days = [1,2,3,4,5]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Senin - Jumat</button>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="1" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Senin</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="2" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Selasa</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="3" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Rabu</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="4" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Kamis</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="5" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Jumat</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="6" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Sabtu</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="7" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Minggu</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-1">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Mulai <span class="text-error-500">*</span></label>
                                            <input type="time" name="jam_mulai" value="{{ $kegiatan->jam_mulai ? \Carbon\Carbon::parse($kegiatan->jam_mulai)->format('H:i') : '' }}" required
                                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                        </div>
                                        <div class="flex-1">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Selesai <span class="text-error-500">*</span></label>
                                            <input type="time" name="jam_selesai" value="{{ $kegiatan->jam_selesai ? \Carbon\Carbon::parse($kegiatan->jam_selesai)->format('H:i') : '' }}" required
                                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="is_active" id="edit_is_active_{{ $kegiatan->id }}" value="1"
                                            {{ $kegiatan->is_active ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                        <label for="edit_is_active_{{ $kegiatan->id }}" class="text-sm text-gray-700 dark:text-gray-300">Kegiatan Aktif</label>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-3">
                                    <button type="button" @click="open = false"
                                        class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                                    <button type="submit"
                                        class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </x-ui.modal>

                    {{-- Modal Hapus Kegiatan --}}
                    <x-ui.modal id="modalHapusKegiatan-{{ $kegiatan->id }}" :is-open="false">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-xl font-bold text-error-500">Hapus Kegiatan</h3>
                                <button @click="open = false" class="text-gray-500 hover:text-gray-800"><i class="fas fa-times"></i></button>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300 mb-2">Yakin ingin menghapus kegiatan:</p>
                            <p class="font-bold text-gray-900 dark:text-white text-lg mb-4">{{ $kegiatan->nama_kegiatan }}</p>
                            <div class="p-3 bg-error-50 text-error-700 rounded-lg text-sm dark:bg-error-500/15 dark:text-error-400 mb-5">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Semua data absensi kegiatan ini akan ikut terhapus.
                            </div>
                            <form action="{{ route('kegiatan.destroy', $kegiatan->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <div class="flex justify-end gap-3">
                                    <button type="button" @click="open = false"
                                        class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                                    <button type="submit"
                                        class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600">Hapus</button>
                                </div>
                            </form>
                        </div>
                    </x-ui.modal>

                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-calendar-times text-4xl text-gray-300 dark:text-gray-600"></i>
                                <p>Belum ada kegiatan. Klik tombol <strong>Tambah Kegiatan</strong> untuk membuat.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($kegiatans->hasPages())
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $kegiatans->links('vendor.pagination.tailwind') }}
    </div>
    @endif
</div>

{{-- Modal Tambah Kegiatan --}}
<x-ui.modal id="modalTambahKegiatan" :is-open="false">
    <div class="p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">
                <i class="fas fa-plus-circle text-brand-500 mr-2"></i>Tambah Kegiatan Baru
            </h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('kegiatan.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kegiatan <span class="text-error-500">*</span></label>
                    <input type="text" name="nama_kegiatan" required placeholder="Contoh: Pramuka, OSIS, Ekskul Basket..."
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                    <textarea name="deskripsi" rows="2" placeholder="Keterangan singkat kegiatan (opsional)"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white"></textarea>
                </div>

                {{-- Kategori / Jenis Kegiatan --}}
                <div x-data="{ kategori: 'sekolah' }" class="space-y-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Kegiatan <span class="text-error-500">*</span></label>
                        <input type="hidden" name="kategori" :value="kategori">
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="kategori = 'sekolah'"
                                :class="kategori === 'sekolah' ? 'border-blue-500 bg-blue-50/70 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500 font-semibold shadow-sm' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                                class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                                <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-blue-600 text-white flex-shrink-0">
                                    <i class="fas fa-school"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-xs">Kegiatan Sekolah</p>
                                    <p class="text-[11px] opacity-75">Upacara, Senam, dll</p>
                                </div>
                            </button>

                            <button type="button" @click="kategori = 'ekskul'"
                                :class="kategori === 'ekskul' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500 font-semibold shadow-sm' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                                class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                                <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-emerald-600 text-white flex-shrink-0">
                                    <i class="fas fa-futbol"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-xs">Ekstrakurikuler</p>
                                    <p class="text-[11px] opacity-75">Pramuka, Futsal, dll</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Dropdown Guru Pembina (Khusus Ekskul) --}}
                    <div x-show="kategori === 'ekskul'" x-transition class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 dark:border-emerald-800 dark:bg-emerald-950/20">
                        <label class="mb-1.5 block text-xs font-semibold text-emerald-900 dark:text-emerald-300">
                            <i class="fas fa-user-tie text-emerald-600 mr-1.5"></i>Guru Pembina / Penanggung Jawab Ekskul <span class="text-error-500">*</span>
                        </label>
                        <select name="pembina_id" :required="kategori === 'ekskul'" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <option value="">-- Pilih Guru Pembina --</option>
                            @foreach($allGurus as $guru)
                                <option value="{{ $guru->id }}">
                                    {{ $guru->nama }} {{ $guru->nip ? '(NIP: ' . $guru->nip . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[11px] text-emerald-700 dark:text-emerald-400">
                            <i class="fas fa-paper-plane mr-1"></i>Laporan rekap kehadiran kegiatan ini akan otomatis dikirimkan ke WhatsApp & Telegram Guru Pembina tersebut.
                        </p>
                    </div>
                </div>

                {{-- Cakupan Peserta Kegiatan --}}
                <div x-data="{
                    targetType: 'all',
                    selectedKelas: [],
                    selectedStudents: [],
                    searchStudent: '',
                    filterKelas: '',
                    allKelasIds: {{ json_encode($allKelas->pluck('id')->toArray()) }}
                }" class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                    <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white">
                        <i class="fas fa-users-cog text-brand-500 mr-1.5"></i>Cakupan Peserta Kegiatan <span class="text-error-500">*</span>
                    </label>
                    <input type="hidden" name="target_type" :value="targetType">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-3">
                        <!-- Opsi 1: Semua Siswa -->
                        <button type="button" @click="targetType = 'all'"
                            :class="targetType === 'all' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300 dark:border-brand-500 shadow-sm font-semibold' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                            class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                            <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-brand-500 text-white flex-shrink-0">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 dark:text-white text-xs">Semua Siswa</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Seluruh siswa sekolah</p>
                            </div>
                        </button>

                        <!-- Opsi 2: Pilih Kelas -->
                        <button type="button" @click="targetType = 'kelas'"
                            :class="targetType === 'kelas' ? 'border-purple-500 bg-purple-50/70 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500 shadow-sm font-semibold' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                            class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                            <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-purple-600 text-white flex-shrink-0">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 dark:text-white text-xs">Pilih Kelas</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Hanya kelas tertentu</p>
                            </div>
                        </button>

                        <!-- Opsi 3: Pilih Siswa Tertentu -->
                        <button type="button" @click="targetType = 'siswa'"
                            :class="targetType === 'siswa' ? 'border-amber-500 bg-amber-50/70 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500 shadow-sm font-semibold' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 hover:border-gray-300'"
                            class="flex items-center gap-2.5 p-3 rounded-xl border text-left text-xs transition">
                            <div class="h-7 w-7 rounded-lg flex items-center justify-center bg-amber-500 text-white flex-shrink-0">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 dark:text-white text-xs">Pilih Siswa</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Siswa khusus (ekskul, dll)</p>
                            </div>
                        </button>
                    </div>

                    <!-- Panel Pemilihan Kelas -->
                    <div x-show="targetType === 'kelas'" x-transition class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Pilih Kelas Peserta (<span x-text="selectedKelas.length" class="text-purple-600 font-bold"></span> dipilih):
                            </span>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" @click="selectedKelas = [...allKelasIds]" class="text-purple-600 hover:underline dark:text-purple-400 font-medium">Pilih Semua</button>
                                <span class="text-gray-300 dark:text-gray-700">•</span>
                                <button type="button" @click="selectedKelas = []" class="text-gray-500 hover:underline dark:text-gray-400 font-medium">Reset</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto p-1">
                            @foreach($allKelas as $kls)
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 bg-white text-xs text-gray-700 cursor-pointer hover:bg-purple-50/50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-purple-500/10 transition">
                                <input type="checkbox" name="kelas_ids[]" :value="{{ $kls->id }}" x-model.number="selectedKelas" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800">
                                <span class="truncate font-medium">{{ $kls->nama_kelas }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Panel Pemilihan Siswa Tertentu -->
                    <div x-show="targetType === 'siswa'" x-transition class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Daftar Siswa Peserta (<span x-text="selectedStudents.length" class="text-amber-600 font-bold"></span> dipilih):
                            </span>
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchStudent" placeholder="Cari nama / NIS..."
                                    class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs outline-none focus:border-amber-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <select x-model="filterKelas" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs outline-none focus:border-amber-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                    <option value="">Semua Kelas</option>
                                    @foreach($allKelas as $kls)
                                    <option value="{{ $kls->id }}">{{ $kls->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="space-y-1.5 max-h-56 overflow-y-auto p-1">
                            @foreach($allStudents as $st)
                            <label x-show="(searchStudent === '' || '{{ strtolower($st->nama . ' ' . $st->nis) }}'.includes(searchStudent.toLowerCase())) && (filterKelas === '' || filterKelas == '{{ $st->kelas_id }}')"
                                class="flex items-center justify-between p-2 rounded-lg border border-gray-200 bg-white text-xs hover:bg-amber-50/40 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-amber-500/10 cursor-pointer transition">
                                <div class="flex items-center gap-2.5">
                                    <input type="checkbox" name="student_ids[]" :value="{{ $st->id }}" x-model.number="selectedStudents" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white">{{ $st->nama }}</p>
                                        <p class="text-[11px] text-gray-400">NIS: {{ $st->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $st->kelas->nama_kelas ?? 'Tanpa Kelas' }}
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="sm:w-1/3">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai <span class="text-error-500">*</span></label>
                        <input type="date" name="tanggal_mulai" value="{{ now()->format('Y-m-d') }}" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="flex-1" x-data="{ selected: 'harian', days: [1, 2, 3, 4, 5, 6, 7] }">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Frekuensi Kegiatan <span class="text-error-500">*</span></label>
                        <input type="hidden" name="frekuensi" :value="selected">
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'sekali'" @change="selected = 'sekali'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Sekali (Insidental)</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'harian'" @change="selected = 'harian'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Harian</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'mingguan'" @change="selected = 'mingguan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Mingguan</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'bulanan'" @change="selected = 'bulanan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Bulanan</span>
                            </label>
                        </div>

                        {{-- Options hari jika harian --}}
                        <div x-show="selected === 'harian'" x-transition class="mt-3 p-3 rounded-xl border border-gray-200 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-900/50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Pilih Hari Kegiatan:</span>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" @click="days = [1,2,3,4,5,6,7]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Semua Hari</button>
                                    <span class="text-gray-300 dark:text-gray-700">•</span>
                                    <button type="button" @click="days = [1,2,3,4,5]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Senin - Jumat</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="1" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Senin</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="2" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Selasa</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="3" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Rabu</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="4" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Kamis</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="5" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Jumat</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="6" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Sabtu</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="7" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Minggu</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Mulai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_mulai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="flex-1">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Selesai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_selesai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <i class="fas fa-save mr-1"></i> Simpan Kegiatan
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

@endsection
