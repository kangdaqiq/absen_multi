@extends('layouts.app')

@section('title', 'Manajemen Device')

@section('content')
    <x-page-header 
        title="Manajemen Device RFID & Biometrik" 
        subtitle="Kelola perangkat mesin scanner tap kartu RFID & biometric sidik jari sekolah" 
        icon="fas fa-microchip"
        x-data>
        <button @click="$dispatch('open-modal', 'modalTambahDevice')"
            class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-brand-500 px-6 py-2.5 text-center text-sm font-medium text-white hover:bg-brand-600 transition shadow-xs">
            <i class="fas fa-plus"></i> Tambah Device
        </button>
    </x-page-header>

    {{-- Counter Ringkasan Sidik Jari Tersimpan di Server --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <!-- Card 1: Total Template -->
        <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Template Server</span>
                    <h4 class="mt-1 text-2xl font-bold text-black dark:text-white">{{ number_format($totalTemplates ?? 0) }}</h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                    <i class="fas fa-database text-lg"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-400">
                <i class="fas fa-check-circle"></i>
                <span>Tersimpan di database sekolah</span>
            </div>
        </div>

        <!-- Card 2: Template Siswa -->
        <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Template Siswa</span>
                    <h4 class="mt-1 text-2xl font-bold text-black dark:text-white">{{ number_format($siswaTemplatesCount ?? 0) }}</h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10">
                    <i class="fas fa-user-graduate text-lg"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                <i class="fas fa-fingerprint"></i>
                <span>Sidik jari siswa aktif</span>
            </div>
        </div>

        <!-- Card 3: Template Guru -->
        <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Template Guru & Staff</span>
                    <h4 class="mt-1 text-2xl font-bold text-black dark:text-white">{{ number_format($guruTemplatesCount ?? 0) }}</h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 text-amber-500 dark:bg-amber-500/10">
                    <i class="fas fa-chalkboard-teacher text-lg"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                <i class="fas fa-fingerprint"></i>
                <span>Sidik jari guru aktif</span>
            </div>
        </div>

        <!-- Card 4: Device Scanner Biometrik -->
        <div class="rounded-sm border border-stroke bg-white p-5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Scanner Fingerprint</span>
                    <h4 class="mt-1 text-2xl font-bold text-black dark:text-white">{{ $devices->where('type', '!=', 'rfid')->count() }} <span class="text-sm font-normal text-gray-500">Unit</span></h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-purple-50 text-purple-500 dark:bg-purple-500/10">
                    <i class="fas fa-fingerprint text-lg"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-purple-600 dark:text-purple-400">
                <i class="fas fa-network-wired"></i>
                <span>Dari total {{ $devices->count() }} unit device</span>
            </div>
        </div>
    </div>

    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
            <h3 class="font-medium text-black dark:text-white">
                Daftar Device
            </h3>
        </div>

        <div class="p-6.5">
            @if(session('success'))
                <div
                    class="mb-6 flex w-full border-l-6 border-success bg-success-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
                    <div class="mr-5 flex h-9 w-full max-w-[36px] items-center justify-center rounded-lg bg-success text-white">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="w-full">
                        <h5 class="mb-1 font-semibold text-[#004434] dark:text-[#34D399]">Berhasil</h5>
                        <p class="text-[#004434] dark:text-[#34D399]">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div
                    class="mb-6 flex w-full border-l-6 border-error bg-error-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
                    <div class="mr-5 flex h-9 w-full max-w-[36px] items-center justify-center rounded-lg bg-error text-white">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="w-full">
                        <h5 class="mb-1 font-semibold text-[#B45454] dark:text-[#F87171]">Gagal</h5>
                        <p class="text-[#B45454] dark:text-[#F87171]">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-sm">
                            <th class="py-4 px-4 font-medium text-black dark:text-white xl:pl-11" width="5%">No</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Nama Device</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Fungsi Scanner</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Token (api_key)</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Tipe Hardware</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Slot ID Sidik Jari</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Status</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $d)
                            <tr>
                                <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11">
                                    <p class="text-black dark:text-white">{{ $loop->iteration }}</p>
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    <p class="text-black dark:text-white font-medium">{{ $d->name }}</p>
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    @if($d->isSholatDevice())
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            <i class="fas fa-mosque"></i> Scanner Sholat
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                            <i class="fas fa-school"></i> Presensi Utama
                                        </span>
                                    @endif
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    <code
                                        class="rounded bg-gray-100 px-2 py-1 text-sm text-brand-500 dark:bg-gray-800 dark:text-brand-300 font-mono">{{ $d->api_key }}</code>
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    @if($d->type == 'rfid')
                                        <span
                                            class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500"><i
                                                class="fas fa-id-card mr-1 mt-0.5"></i> RFID Only</span>
                                    @elseif($d->type == 'fingerprint')
                                        <span
                                            class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-500"><i
                                                class="fas fa-fingerprint mr-1 mt-0.5"></i> Fingerprint Only</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"><i
                                                class="fas fa-microchip mr-1 mt-0.5"></i> RFID + Finger</span>
                                    @endif
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800 text-center">
                                    @if($d->type !== 'rfid')
                                        <div class="flex flex-col items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                                ID {{ $d->finger_id_min ?? 1 }} - {{ $d->finger_id_max ?? 200 }}
                                            </span>
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 inline-flex items-center gap-1 font-medium">
                                                <i class="fas fa-fingerprint text-orange-500 text-[10px]"></i>
                                                <span class="text-black dark:text-white font-semibold">{{ $d->matched_templates_count ?? 0 }}</span> template server
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    @if($d->active)
                                        <span
                                            class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">Active</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                                    @endif
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    <div class="flex items-center space-x-2" x-data>
                                        @if($d->type !== 'rfid')
                                            <form action="{{ route('devices.sync', $d->id) }}" method="POST" class="inline" onsubmit="return confirm('Kirim instruksi sinkronisasi template sidik jari ke {{ addslashes($d->name) }}?')">
                                                @csrf
                                                <button type="submit"
                                                    class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded-lg transition"
                                                    title="Sinkronkan Template ke Alat Ini">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <button
                                            class="text-orange-500 hover:text-orange-700 hover:bg-orange-50 p-2 rounded-lg transition"
                                            @click="$dispatch('open-modal', 'modalEditDevice'); $dispatch('set-edit-device', { id: '{{ $d->id }}', name: '{{ addslashes($d->name) }}', key: '{{ $d->api_key }}', type: '{{ $d->type }}', category: '{{ $d->device_category ?? 'attendance' }}', finger_min: '{{ $d->finger_id_min ?? 1 }}', finger_max: '{{ $d->finger_id_max ?? 200 }}', active: '{{ $d->active }}' })"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition"
                                            @click="$dispatch('open-modal', 'modalHapusDevice'); $dispatch('set-delete-device', { id: '{{ $d->id }}', name: '{{ addslashes($d->name) }}' })"
                                            title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Log Sinkronisasi & Pendaftaran Template Sidik Jari --}}
    <div class="mt-6 rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="font-medium text-black dark:text-white flex items-center gap-2">
                    <i class="fas fa-history text-brand-500"></i> Log Sinkronisasi & Pendaftaran Template Sidik Jari
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    15 riwayat aktivitas transfer template, download ke alat, dan pendaftaran sidik jari terbaru
                </p>
            </div>
            <div>
                <a href="{{ route('api-logs.index', ['tab' => 'sync_template']) }}" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-500 hover:text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-500/10 px-3 py-1.5 rounded-lg transition">
                    <span>Buka Semua di Log API</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <div class="p-6.5">
            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <th class="py-3 px-4 font-semibold" width="16%">Waktu</th>
                            <th class="py-3 px-4 font-semibold" width="18%">Aktivitas</th>
                            <th class="py-3 px-4 font-semibold text-center" width="10%">Slot ID</th>
                            <th class="py-3 px-4 font-semibold">Keterangan / Detail</th>
                            <th class="py-3 px-4 font-semibold text-center" width="10%">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($syncLogs ?? [] as $log)
                            <tr>
                                <td class="py-3 px-4 font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') : '-' }}
                                    <div class="text-[10px] text-gray-400">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    @if($log->action === 'sync_template_download' || $log->action === 'sync_template')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                            <i class="fas fa-download text-[10px]"></i> Download Template
                                        </span>
                                    @elseif($log->action === 'sync_template_list')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            <i class="fas fa-list text-[10px]"></i> Cek Daftar Sync
                                        </span>
                                    @elseif($log->action === 'sync_template_result')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            <i class="fas fa-check-double text-[10px]"></i> Hasil Sync Alat
                                        </span>
                                    @elseif($log->action === 'sync_requested')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                            <i class="fas fa-paper-plane text-[10px]"></i> Trigger Sync
                                        </span>
                                    @elseif($log->action === 'enroll_success')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            <i class="fas fa-fingerprint text-[10px]"></i> Enroll Sukses
                                        </span>
                                    @elseif($log->action === 'enroll_failed')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/15 dark:text-red-400 border border-red-200 dark:border-red-800">
                                            <i class="fas fa-times-circle text-[10px]"></i> Enroll Gagal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $log->action }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($log->uid)
                                        <code class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded font-semibold text-xs text-brand-600 dark:text-brand-400">#{{ $log->uid }}</code>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-black dark:text-white">
                                    <p class="text-xs sm:text-sm font-medium">{{ $log->message }}</p>
                                    @if($log->ip_address)
                                        <span class="text-[11px] text-gray-400">IP: {{ $log->ip_address }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($log->success)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                            <i class="fas fa-check-circle"></i> Berhasil
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 dark:text-red-400">
                                            <i class="fas fa-times-circle"></i> Gagal
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-fingerprint text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                                        <p class="font-medium text-sm">Belum ada riwayat aktivitas sinkronisasi template.</p>
                                        <p class="text-xs text-gray-400 mt-1">Log akan otomatis tercatat saat scanner mengunduh template atau saat pendaftaran sidik jari dilakukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <x-ui.modal id="modalTambahDevice">
        <div class="p-6">
            <h3 class="mb-5 text-2xl font-bold text-gray-800 dark:text-white/90">Tambah Device</h3>
            <form action="{{ route('devices.store') }}" method="POST">
                @csrf
                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Nama Device <span
                            class="text-meta-1">*</span></label>
                    <input type="text" name="name" required
                        class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Token (api_key) <span
                            class="text-meta-1">*</span></label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="api_key" id="create_api_key" required
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <button type="button" onclick="generateToken('create_api_key')"
                            class="rounded bg-gray-200 px-4 py-3 text-sm font-medium text-black hover:bg-gray-300 dark:bg-meta-4 dark:text-white dark:hover:bg-meta-3 transition">Generate</button>
                    </div>
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Tipe Device</label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="type"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="rfid_fingerprint">RFID + Fingerprint (Default)</option>
                            <option value="rfid">RFID Only</option>
                            <option value="fingerprint">Fingerprint Only</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Fungsi Scanner <span class="text-meta-1">*</span></label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="device_category" required
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="attendance">Presensi Utama (Masuk & Pulang Gerbang/Kelas)</option>
                            <option value="sholat">Presensi Sholat (Khusus Musholla / Masjid)</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Pilih "Presensi Sholat" agar kartu yang di-tap pada alat ini dicatat sebagai kehadiran sholat berdasarkan jadwal koordinat sekolah.</span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4.5">
                    <div>
                        <label class="mb-2.5 block text-black dark:text-white">Slot ID Min (Sidik Jari)</label>
                        <input type="number" name="finger_id_min" value="1" min="1" max="1000"
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <span class="text-xs text-gray-500 mt-1 block">Contoh: 1, 150, 300</span>
                    </div>
                    <div>
                        <label class="mb-2.5 block text-black dark:text-white">Slot ID Max (Sidik Jari)</label>
                        <input type="number" name="finger_id_max" value="200" min="1" max="1000"
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <span class="text-xs text-gray-500 mt-1 block">Contoh: 200, 300, 600</span>
                    </div>
                </div>

                <div class="mb-5.5">
                    <label class="mb-2.5 block text-black dark:text-white">Status</label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="active"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-stroke pt-4 dark:border-strokedark">
                    <button type="button" @click="open = false"
                        class="rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">Batal</button>
                    <button type="submit"
                        class="rounded bg-brand-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">Simpan</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Edit -->
    <x-ui.modal id="modalEditDevice">
        <div class="p-6" x-data="{ 
                id: '', name: '', key: '', type: 'rfid_fingerprint', category: 'attendance', finger_min: 1, finger_max: 200, active: '1',
                actionUrl: ''
            }" @set-edit-device.window="
                id = $event.detail.id;
                name = $event.detail.name;
                key = $event.detail.key;
                type = $event.detail.type;
                category = $event.detail.category || 'attendance';
                finger_min = $event.detail.finger_min || 1;
                finger_max = $event.detail.finger_max || 200;
                active = $event.detail.active;
                actionUrl = '{{ url('devices') }}/' + id;
            ">
            <form :action="actionUrl" method="POST">
                @csrf
                @method('PUT')

                <h3 class="mb-5 text-2xl font-bold text-gray-800 dark:text-white/90">Edit Device</h3>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Nama Device <span
                            class="text-meta-1">*</span></label>
                    <input type="text" name="name" x-model="name" required
                        class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Token (api_key) <span
                            class="text-meta-1">*</span></label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="api_key" id="edit_api_key" x-model="key" required
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <button type="button" @click="key = generateTokenString()"
                            class="rounded bg-gray-200 px-4 py-3 text-sm font-medium text-black hover:bg-gray-300 dark:bg-meta-4 dark:text-white dark:hover:bg-meta-3 transition">Generate</button>
                    </div>
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Tipe Device</label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="type" x-model="type"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="rfid_fingerprint">RFID + Fingerprint</option>
                            <option value="rfid">RFID Only</option>
                            <option value="fingerprint">Fingerprint Only</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Fungsi Scanner <span class="text-meta-1">*</span></label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="device_category" x-model="category" required
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="attendance">Presensi Utama (Masuk & Pulang Gerbang/Kelas)</option>
                            <option value="sholat">Presensi Sholat (Khusus Musholla / Masjid)</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Jika diset ke "Presensi Sholat", tap kartu di perangkat ini langsung dicatat ke Absensi Sholat.</span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4.5">
                    <div>
                        <label class="mb-2.5 block text-black dark:text-white">Slot ID Min (Sidik Jari)</label>
                        <input type="number" name="finger_id_min" x-model="finger_min" min="1" max="1000"
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <span class="text-xs text-gray-500 mt-1 block">Contoh: 1, 150, 300</span>
                    </div>
                    <div>
                        <label class="mb-2.5 block text-black dark:text-white">Slot ID Max (Sidik Jari)</label>
                        <input type="number" name="finger_id_max" x-model="finger_max" min="1" max="1000"
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <span class="text-xs text-gray-500 mt-1 block">Contoh: 200, 300, 600</span>
                    </div>
                </div>

                <div class="mb-5.5">
                    <label class="mb-2.5 block text-black dark:text-white">Status</label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="active" x-model="active"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-stroke pt-4 dark:border-strokedark">
                    <button type="button" @click="open = false"
                        class="rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">Batal</button>
                    <button type="submit"
                        class="rounded bg-brand-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">Update</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Hapus -->
    <x-ui.modal id="modalHapusDevice">
        <div class="p-6" x-data="{ id: '', name: '', actionUrl: '' }" @set-delete-device.window="
                id = $event.detail.id;
                name = $event.detail.name;
                actionUrl = '{{ url('devices') }}/' + id;
            ">
            <form :action="actionUrl" method="POST">
                @csrf
                @method('DELETE')
                <div class="mb-5.5 flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-danger/20 text-danger">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-black dark:text-white">Konfirmasi Hapus</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Yakin ingin menghapus device <strong class="text-black dark:text-white" x-text="name"></strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-stroke pt-4 dark:border-strokedark">
                    <button type="button" @click="open = false"
                        class="rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">Batal</button>
                    <button type="submit"
                        class="rounded bg-red-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">Ya,
                        Hapus</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

@endsection

@push('scripts')
    <script>
        function generateTokenString() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let out = '';
            for (let i = 0; i < 60; i++) out += chars.charAt(Math.floor(Math.random() * chars.length));
            return out;
        }

        function generateToken(targetId) {
            document.getElementById(targetId).value = generateTokenString();
        }
    </script>
@endpush