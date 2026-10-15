@extends('layouts.app')

@section('title', 'Manajemen User & Akun Guru')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
            <i class="fas fa-users-cog text-brand-500"></i> Manajemen User
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Kelola pengguna sistem, hak akses, serta generate akun guru secara otomatis.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <button type="button" @click="$dispatch('open-modal', 'modalGenerateGuru')"
            class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-center font-bold text-white shadow-sm transition hover:opacity-95"
            style="background-color: #059669; color: #ffffff;">
            <i class="fas fa-wand-magic-sparkles text-white"></i>
            <span class="text-white">Generate Akun Guru</span>
            @if(isset($unregisteredGurus) && $unregisteredGurus->count() > 0)
                <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-bold text-white" style="background-color: rgba(255, 255, 255, 0.25);">
                    {{ $unregisteredGurus->count() }}
                </span>
            @endif
        </button>

        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 hover:bg-brand-600 px-4 py-2.5 text-center font-medium text-white shadow-sm transition">
            <i class="fas fa-plus"></i> Tambah User
        </a>
    </div>
</div>

@if (session('success'))
    <div class="mb-5 rounded-xl bg-success/10 border border-success/20 p-4 text-sm font-medium text-success flex items-center gap-3">
        <i class="fas fa-check-circle text-lg"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="mb-5 rounded-xl bg-error-500/10 border border-error-500/20 p-4 text-sm font-medium text-error-500 flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-lg"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

{{-- ========================= CARD KREDENSIAL HASIL GENERATE ========================= --}}
@if(session('generated_accounts'))
<div class="mb-6 rounded-2xl border-2 border-emerald-500/40 bg-gradient-to-br from-emerald-500/5 via-white to-emerald-500/10 dark:from-emerald-950/30 dark:via-gray-900 dark:to-emerald-900/20 p-6 shadow-lg">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-emerald-500/20">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-xs font-bold mb-2">
                <i class="fas fa-key"></i> Kredensial Baru Dibuat
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                Hasil Generate Akun Guru ({{ count(session('generated_accounts')) }} Akun)
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                Password di bawah dibuat secara acak. Segera salin atau cetak sebelum meninggalkan halaman ini.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" id="btnCopyAllGenerated" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-bold text-sm transition shadow-sm hover:opacity-95 cursor-pointer"
                style="background-color: #059669; color: #ffffff;">
                <i class="fas fa-copy text-white"></i> <span class="text-white">Salin Semua (Format WA)</span>
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 transition cursor-pointer">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-white dark:bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead class="bg-emerald-50/80 dark:bg-emerald-950/40 text-xs uppercase font-semibold text-emerald-800 dark:text-emerald-300 border-b border-emerald-100 dark:border-emerald-900/40">
                <tr>
                    <th class="py-3 px-4">No</th>
                    <th class="py-3 px-4">Nama Guru</th>
                    <th class="py-3 px-4">NIP</th>
                    <th class="py-3 px-4">Username (Aturan Maks 2 Kata)</th>
                    <th class="py-3 px-4">Password Auto Generate</th>
                    <th class="py-3 px-4 text-center">Status</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-mono text-xs">
                @foreach(session('generated_accounts') as $idx => $acc)
                <tr class="hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20 transition-colors">
                    <td class="py-3 px-4 font-sans text-gray-500">{{ $idx + 1 }}</td>
                    <td class="py-3 px-4 font-sans font-semibold text-gray-900 dark:text-white">{{ $acc['nama'] }}</td>
                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $acc['nip'] }}</td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-bold">
                            {{ $acc['username'] }}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 font-bold">
                            {{ $acc['password'] }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center font-sans">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ ($acc['status'] ?? '') == 'Password Direset' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                            {{ $acc['status'] ?? 'Akun Baru' }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center font-sans">
                        <button type="button" class="btnCopySingle px-2.5 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition"
                            data-copy="Nama: {{ $acc['nama'] }}&#10;Username: {{ $acc['username'] }}&#10;Password: {{ $acc['password'] }}"
                            title="Salin Akun Ini">
                            <i class="fas fa-copy mr-1"></i> Salin
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ========================= TABEL UTAMA PENGGUNA ========================= --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-boxdark">
    {{-- Header & Filters --}}
    <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h3 class="font-bold text-lg text-gray-800 dark:text-white flex items-center gap-2">
                Daftar Pengguna Sistem
                <span class="rounded-full bg-brand-500/10 px-2.5 py-0.5 text-xs font-semibold text-brand-500">
                    {{ $users->count() }} Pengguna
                </span>
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Menampilkan akun admin, guru, wali kelas, dan waka kurikulum.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Bulk delete --}}
            <button type="button" id="bulkDeleteBtn" class="hidden inline-flex items-center justify-center gap-2 rounded-lg bg-error-500 px-3.5 py-2 text-sm font-medium text-white hover:bg-error-600 transition">
                <i class="fas fa-trash"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
            </button>

            {{-- Role Filter Bar --}}
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-1 text-xs font-medium">
                <a href="{{ route('users.index') }}" class="px-3 py-1.5 rounded-md transition {{ !request('role') ? 'bg-white dark:bg-gray-700 text-brand-600 dark:text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                    Semua
                </a>
                <a href="{{ route('users.index', ['role' => 'guru']) }}" class="px-3 py-1.5 rounded-md transition {{ request('role') == 'guru' ? 'text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                    style="{{ request('role') == 'guru' ? 'background-color: #059669; color: #ffffff;' : '' }}">
                    Guru Pengajar
                </a>
                <a href="{{ route('users.index', ['role' => 'wali_kelas']) }}" class="px-3 py-1.5 rounded-md transition {{ request('role') == 'wali_kelas' ? 'bg-orange-500 text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-orange-500' }}">
                    Wali Kelas
                </a>
                <a href="{{ route('users.index', ['role' => 'waka_kurikulum']) }}" class="px-3 py-1.5 rounded-md transition {{ request('role') == 'waka_kurikulum' ? 'bg-purple-600 text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-purple-600' }}">
                    Waka Kurikulum
                </a>
                <a href="{{ route('users.index', ['role' => 'admin']) }}" class="px-3 py-1.5 rounded-md transition {{ request('role') == 'admin' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-blue-600' }}">
                    Admin
                </a>
            </div>
        </div>
    </div>
    
    <div class="p-0">
        <form id="bulkDeleteForm" action="{{ route('users.bulk-destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            
            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50/70 text-left dark:bg-gray-800/40 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <th class="py-3.5 px-4 xl:pl-6" width="30">
                                <label for="selectAll" class="flex cursor-pointer select-none items-center">
                                    <div class="relative">
                                        <input type="checkbox" id="selectAll" class="sr-only peer" />
                                        <div class="box flex h-4.5 w-4.5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark peer-checked:border-brand-500 peer-checked:bg-brand-500 transition">
                                            <span class="opacity-0 peer-checked:opacity-100">
                                                <i class="fas fa-check text-xs text-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </th>
                            <th class="py-3.5 px-4 font-semibold text-gray-700 dark:text-gray-300">Nama Lengkap & Info Profil</th>
                            <th class="py-3.5 px-4 font-semibold text-gray-700 dark:text-gray-300">Username</th>
                            <th class="py-3.5 px-4 font-semibold text-gray-700 dark:text-gray-300">Role Sistem</th>
                            <th class="py-3.5 px-4 font-semibold text-gray-700 dark:text-gray-300">Email Address</th>
                            <th class="py-3.5 px-4 font-semibold text-gray-700 dark:text-gray-300 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 text-sm">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="py-4 px-4 pl-6">
                                @if(auth()->id() !== $user->id)
                                <label for="user_{{ $user->id }}" class="flex cursor-pointer select-none items-center">
                                    <div class="relative">
                                        <input type="checkbox" id="user_{{ $user->id }}" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox sr-only peer" />
                                        <div class="box flex h-4.5 w-4.5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark peer-checked:border-brand-500 peer-checked:bg-brand-500 transition">
                                            <span class="opacity-0 peer-checked:opacity-100">
                                                <i class="fas fa-check text-xs text-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400 font-bold flex items-center justify-center text-sm">
                                        {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-gray-900 dark:text-white font-semibold">{{ $user->full_name }}</p>
                                        @if($user->guru)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400">
                                                <i class="fas fa-chalkboard-teacher"></i> Terhubung Data Guru {{ $user->guru->nip ? '('.$user->guru->nip.')' : '' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 px-3 py-1 font-mono text-xs font-semibold text-gray-800 dark:text-gray-200">
                                    <i class="fas fa-user text-[10px] text-gray-400"></i> {{ $user->username }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                @if(in_array($user->role, ['guru', 'teacher']))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-400 border border-success-200 dark:border-success-500/20">
                                        <i class="fas fa-chalkboard-teacher"></i> Guru Pengajar
                                    </span>
                                @elseif($user->role === 'wali_kelas')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-orange-500/10 px-3 py-1 text-xs font-semibold text-orange-600 dark:text-orange-400 border border-orange-500/20">
                                        <i class="fas fa-user-graduate"></i> Wali Kelas
                                    </span>
                                @elseif($user->role === 'waka_kurikulum')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-500/10 px-3 py-1 text-xs font-semibold text-purple-600 dark:text-purple-400 border border-purple-500/20">
                                        <i class="fas fa-book-reader"></i> Waka Kurikulum
                                    </span>
                                @elseif($user->role === 'super_admin')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                        <i class="fas fa-shield-alt"></i> Super Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-3 py-1 text-xs font-semibold text-blue-600 dark:text-blue-400 border border-blue-500/20">
                                        <i class="fas fa-user-shield"></i> Admin
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <p class="text-gray-600 dark:text-gray-400 text-xs">{{ $user->email }}</p>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Edit --}}
                                    <button type="button" class="btnEditUser text-amber-500 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-500/10 p-2 rounded-lg transition"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->full_name }}"
                                        data-username="{{ $user->username }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->role }}"
                                        data-update-url="{{ route('users.update', $user->id) }}"
                                        @click.prevent="$dispatch('open-modal', 'modalEditUser')"
                                        title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- Delete --}}
                                    @if(auth()->id() !== $user->id)
                                    <button type="button" class="btnDeleteUser text-error-500 hover:text-error-700 hover:bg-error-50 dark:hover:bg-error-500/10 p-2 rounded-lg transition"
                                        data-id="{{ $user->id }}" data-name="{{ $user->full_name }}" title="Hapus User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400 dark:text-gray-500">
                                <i class="fas fa-user-slash text-3xl mb-2 block"></i>
                                Tidak ada data pengguna yang sesuai.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

{{-- ========================= MODAL GENERATE AKUN GURU ========================= --}}
<x-ui.modal id="modalGenerateGuru" :is-open="false" class="max-w-2xl">
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Generate Akun Guru Otomatis
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Buat akun login sistem untuk guru pengajar dalam hitungan detik.
                    </p>
                </div>
            </div>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form action="{{ route('users.generate-guru') }}" method="POST" class="mt-5 space-y-5" id="formGenerateGuru">
            @csrf

            {{-- Mode Pemilihan --}}
            <div>
                <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-2">
                    Pilihan Target Guru
                </label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ mode: 'all' }">
                    <label class="cursor-pointer relative flex flex-col p-4 rounded-xl border-2 transition"
                        :class="mode === 'all' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-500/10 ring-1 ring-brand-500' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300'">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-sm text-gray-900 dark:text-white">Semua Guru Belum Ada Akun</span>
                            <input type="radio" name="mode" value="all" x-model="mode" class="text-brand-500 focus:ring-brand-500" checked />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Otomatis memproses <strong>{{ isset($unregisteredGurus) ? $unregisteredGurus->count() : 0 }} guru</strong> yang belum memiliki akun user.
                        </p>
                    </label>

                    <label class="cursor-pointer relative flex flex-col p-4 rounded-xl border-2 transition"
                        :class="mode === 'selected' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-500/10 ring-1 ring-brand-500' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300'">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-sm text-gray-900 dark:text-white">Pilih Guru Tertentu</span>
                            <input type="radio" name="mode" value="selected" x-model="mode" class="text-brand-500 focus:ring-brand-500" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Pilih secara spesifik dari daftar guru sekolah.
                        </p>
                    </label>

                    {{-- Container Checklist Guru Spesifik --}}
                    <div x-show="mode === 'selected'" x-cloak class="sm:col-span-2 pt-2" style="display: none;">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-3 bg-gray-50/50 dark:bg-gray-900/40">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Daftar Guru ({{ isset($allGurus) ? $allGurus->count() : 0 }})
                                </span>
                                <input type="text" id="searchGuruInModal" placeholder="Cari nama guru..."
                                    class="text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1 outline-none focus:border-brand-500" />
                            </div>

                            <div class="max-h-52 overflow-y-auto space-y-1.5 pr-1 divide-y divide-gray-100 dark:divide-gray-800" id="guruChecklistContainer">
                                @if(isset($allGurus))
                                    @foreach($allGurus as $g)
                                    <label class="guru-item-row flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer text-xs"
                                        data-name="{{ strtolower($g->nama) }}">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" name="guru_ids[]" value="{{ $g->id }}"
                                                class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 guru-modal-cb"
                                                {{ $g->user_id ? '' : 'checked' }} />
                                            <div>
                                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $g->nama }}</span>
                                                <span class="text-[11px] text-gray-400 ml-1">{{ $g->nip ? 'NIP: '.$g->nip : '' }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            @if($g->user_id)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                    Sudah Ada Akun
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold text-white" style="background-color: #059669;">
                                                    Belum Ada Akun
                                                </span>
                                            @endif
                                        </div>
                                    </label>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Opsi Reset Password Jika Sudah Punya Akun --}}
            <div class="pt-2">
                <label class="flex items-start gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" name="reset_existing" value="1"
                        class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 mt-0.5" />
                    <div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                            Reset & generate password baru jika guru sudah memiliki akun
                        </span>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            Centang ini jika Anda ingin mengganti password akun guru yang sudah ada dengan password baru yang di-generate.
                        </p>
                    </div>
                </label>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                <button type="button" @click="open = false"
                    class="rounded-xl border border-gray-300 dark:border-gray-700 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-xl px-6 py-2.5 text-sm font-bold text-white shadow-md transition flex items-center gap-2 hover:opacity-95 cursor-pointer"
                    style="background-color: #059669; color: #ffffff;">
                    <i class="fas fa-wand-magic-sparkles text-white"></i>
                    <span class="text-white">Generate Akun Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

{{-- ========================= MODAL EDIT USER ========================= --}}
<x-ui.modal id="modalEditUser" :is-open="false" class="max-w-xl">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit User</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formEditUser" action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Lengkap <span class="text-error-500">*</span>
                </label>
                <input type="text" name="name" id="edit_user_name" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Username <span class="text-error-500">*</span>
                </label>
                <input type="text" name="username" id="edit_user_username" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Email <span class="text-error-500">*</span>
                </label>
                <input type="email" name="email" id="edit_user_email" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Role <span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <select name="role" id="edit_user_role" required
                        class="w-full appearance-none rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="guru">Guru Pengajar</option>
                        <option value="admin">Admin</option>
                        @if(auth()->user() && auth()->user()->isSuperAdmin())
                        <option value="super_admin">Super Admin</option>
                        @endif
                        <option value="wali_kelas">Wali Kelas</option>
                        <option value="waka_kurikulum">Waka Kurikulum</option>
                    </select>
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </span>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-200 dark:border-gray-800">
                <p class="text-sm font-medium text-brand-500 mb-1">Ganti Password <span class="text-gray-400 font-normal">(Opsional)</span></p>
                <p class="text-xs text-gray-500 mb-3">Kosongkan jika tidak ingin mengubah password</p>
                <div class="flex gap-3">
                    <div class="w-1/2">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Password Baru</label>
                        <input type="password" name="password"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
                        <p class="mt-1 text-xs text-gray-400">Min. 8 karakter</p>
                    </div>
                    <div class="w-1/2">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600 transition">
                    <i class="fas fa-save mr-1"></i> Update
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll       = document.getElementById('selectAll');
    const userCheckboxes  = document.querySelectorAll('.user-checkbox');
    const bulkDeleteBtn   = document.getElementById('bulkDeleteBtn');
    const selectedCount   = document.getElementById('selectedCount');
    const bulkDeleteForm  = document.getElementById('bulkDeleteForm');

    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
        if (selectedCount) selectedCount.textContent = checkedCount;
        if (bulkDeleteBtn) bulkDeleteBtn.classList.toggle('hidden', checkedCount === 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            userCheckboxes.forEach(cb => { cb.checked = this.checked; });
            updateBulkDeleteButton();
        });
    }

    userCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateBulkDeleteButton();
            if (selectAll) {
                selectAll.checked = document.querySelectorAll('.user-checkbox:checked').length === userCheckboxes.length;
            }
        });
    });

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const count = document.querySelectorAll('.user-checkbox:checked').length;
            if (count === 0) { alert('Pilih minimal 1 user untuk dihapus.'); return; }
            if (confirm(`Hapus ${count} user yang dipilih?`)) bulkDeleteForm.submit();
        });
    }

    // ===== Modal Edit User =====
    document.querySelectorAll('.btnEditUser').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('formEditUser').action = this.dataset.updateUrl;
            document.getElementById('edit_user_name').value     = this.dataset.name;
            document.getElementById('edit_user_username').value = this.dataset.username;
            document.getElementById('edit_user_email').value    = this.dataset.email;
            
            // Map 'teacher' ke 'guru' jika perlu
            let roleVal = this.dataset.role;
            if (roleVal === 'teacher') roleVal = 'guru';
            document.getElementById('edit_user_role').value     = roleVal;

            // Clear password fields
            document.querySelectorAll('#formEditUser input[type=password]').forEach(i => i.value = '');
        });
    });

    // ===== Individual Delete =====
    document.querySelectorAll('.btnDeleteUser').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const userId   = this.getAttribute('data-id');
            const userName = this.getAttribute('data-name');

            if (confirm(`Hapus user ${userName}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ url('users') }}/${userId}`;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden'; csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden'; methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // ===== Search Guru Checklist in Modal =====
    const searchGuruInput = document.getElementById('searchGuruInModal');
    if (searchGuruInput) {
        searchGuruInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('#guruChecklistContainer .guru-item-row').forEach(row => {
                const name = row.getAttribute('data-name');
                if (name.includes(query)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // ===== Copy Single Account =====
    document.querySelectorAll('.btnCopySingle').forEach(btn => {
        btn.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            navigator.clipboard.writeText(textToCopy).then(() => {
                const orig = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check mr-1 text-emerald-600"></i> Disalin!';
                setTimeout(() => { this.innerHTML = orig; }, 2000);
            });
        });
    });

    // ===== Copy All Generated Accounts =====
    const btnCopyAll = document.getElementById('btnCopyAllGenerated');
    if (btnCopyAll) {
        btnCopyAll.addEventListener('click', function() {
            @if(session('generated_accounts'))
            const accounts = @json(session('generated_accounts'));
            let text = "DAFTAR AKUN LOGIN GURU\n";
            text += "===================================\n";
            accounts.forEach((acc, i) => {
                text += `${i + 1}. ${acc.nama}\n`;
                text += `   Username : ${acc.username}\n`;
                text += `   Password : ${acc.password}\n`;
                text += "-----------------------------------\n";
            });
            text += "\nSilakan gunakan kredensial di atas untuk masuk ke sistem.";

            navigator.clipboard.writeText(text).then(() => {
                const origText = btnCopyAll.innerHTML;
                btnCopyAll.innerHTML = '<i class="fas fa-check text-white"></i> Berhasil Disalin ke Clipboard!';
                btnCopyAll.style.backgroundColor = '#047857';
                setTimeout(() => {
                    btnCopyAll.innerHTML = origText;
                    btnCopyAll.style.backgroundColor = '#059669';
                }, 3000);
            });
            @endif
        });
    }
});
</script>
@endpush
