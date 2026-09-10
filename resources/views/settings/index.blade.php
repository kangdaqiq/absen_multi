@extends('layouts.app')

@section('title', 'Pengaturan Sekolah')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Pengaturan Sekolah
        </h2>
    </div>

    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div x-data="{ activeTab: 'general' }"
            class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <!-- Tabs Header -->
            <div class="border-b border-gray-200 px-5 dark:border-gray-800">
                <nav class="flex gap-4">
                    <button @click.prevent="activeTab = 'general'"
                        :class="activeTab === 'general' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                        class="border-b-2 py-4 px-2 text-sm font-medium transition-colors">
                        Umum
                    </button>
                    <button @click.prevent="activeTab = 'automation'"
                        :class="activeTab === 'automation' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                        class="border-b-2 py-4 px-2 text-sm font-medium transition-colors">
                        Otomatisasi & Notifikasi
                    </button>
                    <button @click.prevent="activeTab = 'geofence'; setTimeout(() => { if (window.initGeofenceMap) window.initGeofenceMap(); }, 150);"
                        :class="activeTab === 'geofence' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                        class="border-b-2 py-4 px-2 text-sm font-medium transition-colors flex items-center gap-1.5">
                        <i class="fas fa-map-marker-alt text-xs"></i> Lokasi & Geofence (Mobile)
                    </button>
                    @if(config('app.mode') === 'self_hosted' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()))
                    <button @click.prevent="activeTab = 'license'"
                        :class="activeTab === 'license' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                        class="border-b-2 py-4 px-2 text-sm font-medium transition-colors">
                        Lisensi Aplikasi
                    </button>
                    @endif
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Tab Umum -->
                <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">

                    <h3
                        class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2">
                        Konfigurasi Umum</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-6">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama
                                    Sekolah</label>
                                <input type="text" name="nama_sekolah"
                                    value="{{ $settings['nama_sekolah'] ?? 'SMK Assuniyah Tumijajar' }}"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat
                                    Sekolah (Kop Surat)</label>
                                <textarea name="alamat_sekolah" rows="3"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">{{ $settings['alamat_sekolah'] ?? '' }}</textarea>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kota / Lokasi
                                    Tanda Tangan (Laporan)</label>
                                <input type="text" name="alamat_ttd" value="{{ $settings['alamat_ttd'] ?? 'Jakarta' }}"
                                    placeholder="Contoh: Jakarta"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Zona Waktu (Timezone)</label>
                                @php
                                    $currentTimezone = $settings['timezone'] ?? config('app.timezone', 'Asia/Jakarta');
                                @endphp
                                <select name="timezone"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                    <option value="Asia/Jakarta" {{ $currentTimezone === 'Asia/Jakarta' ? 'selected' : '' }}>WIB - Waktu Indonesia Barat (UTC+07:00)</option>
                                    <option value="Asia/Makassar" {{ $currentTimezone === 'Asia/Makassar' ? 'selected' : '' }}>WITA - Waktu Indonesia Tengah (UTC+08:00)</option>
                                    <option value="Asia/Jayapura" {{ $currentTimezone === 'Asia/Jayapura' ? 'selected' : '' }}>WIT - Waktu Indonesia Timur (UTC+09:00)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Zona waktu digunakan untuk sinkronisasi jam absensi, laporan, dan jadwal otomatis.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kepala Sekolah</label>
                                    <input type="text" name="nama_kepala_sekolah" value="{{ $settings['nama_kepala_sekolah'] ?? '' }}"
                                        placeholder="Contoh: Drs. Ahmad Fauzi, M.Pd"
                                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">NIP Kepala Sekolah</label>
                                    <input type="text" name="nip_kepala_sekolah" value="{{ $settings['nip_kepala_sekolah'] ?? '' }}"
                                        placeholder="Masukkan NIP"
                                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Waka Kesiswaan</label>
                                    <input type="text" name="nama_waka_kesiswaan" value="{{ $settings['nama_waka_kesiswaan'] ?? '' }}"
                                        placeholder="Contoh: Siti Nurhaliza, S.Pd"
                                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">NIP Waka Kesiswaan</label>
                                    <input type="text" name="nip_waka_kesiswaan" value="{{ $settings['nip_waka_kesiswaan'] ?? '' }}"
                                        placeholder="Masukkan NIP"
                                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                </div>
                            </div>
                        </div>{{-- end kolom kiri --}}

                        <div class="space-y-6">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Logo
                                    Sekolah</label>
                                <div class="mb-3">
                                    @php
                                        $logo = !empty($settings['logo_filename']) ? $settings['logo_filename'] : 'logo.png';
                                        $isStorage = \Illuminate\Support\Str::startsWith($logo, 'schools/');
                                        $logoUrl = asset('img/logo.png'); // Default

                                        if ($isStorage && file_exists(storage_path('app/public/' . $logo))) {
                                            $logoUrl = asset('storage/' . $logo);
                                        } elseif (!$isStorage && file_exists(public_path('img/' . $logo))) {
                                            $logoUrl = asset('img/' . $logo);
                                        }
                                    @endphp
                                    <div
                                        class="rounded-lg border border-gray-200 p-2 dark:border-gray-800 inline-block bg-white dark:bg-gray-800">
                                        <img src="{{ $logoUrl }}" alt="Logo" id="logo-preview" class="h-24 object-contain">
                                    </div>
                                </div>
                                <input type="file" name="logo" id="logo-input" accept="image/*"
                                    class="w-full cursor-pointer rounded-lg border border-gray-200 bg-transparent text-sm outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-800 dark:file:text-white dark:hover:file:bg-gray-700">
                                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, GIF, SVG. Maksimal 10MB</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kop Surat
                                    Sekolah (Header Laporan)</label>
                                <div class="mb-3">
                                    @php
                                        $kopSurat = !empty($settings['kop_surat']) ? $settings['kop_surat'] : 'default_kop.png';
                                        $isStorageKop = \Illuminate\Support\Str::startsWith($kopSurat, 'schools/');
                                        $kopUrl = asset('img/default_kop.png'); // Default

                                        if ($isStorageKop && file_exists(storage_path('app/public/' . $kopSurat))) {
                                            $kopUrl = asset('storage/' . $kopSurat);
                                        } elseif (!$isStorageKop && file_exists(public_path('img/' . $kopSurat))) {
                                            $kopUrl = asset('img/' . $kopSurat);
                                        }
                                    @endphp
                                    <div
                                        class="rounded-lg border border-gray-200 p-2 dark:border-gray-800 bg-white dark:bg-gray-800 w-full overflow-hidden flex justify-center">
                                        <img src="{{ $kopUrl }}" alt="Kop Surat" id="kop-preview" class="h-24 object-contain max-w-full">
                                    </div>
                                </div>
                                <input type="file" name="kop_surat" id="kop-input" accept="image/jpeg,image/png,image/jpg"
                                    class="w-full cursor-pointer rounded-lg border border-gray-200 bg-transparent text-sm outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-800 dark:file:text-white dark:hover:file:bg-gray-700">
                                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG. Rekomendasi resolusi lebar (contoh: 1200x200px). Maksimal 5MB.</p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                                <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">Pengaturan Absen
                                    Pulang</h4>

                                <div class="mb-4">
                                    <label class="flex items-center cursor-pointer select-none">
                                        <div class="relative">
                                            <input type="checkbox" id="enable_checkout_attendance"
                                                name="enable_checkout_attendance" value="true" class="sr-only" {{ ($settings['enable_checkout_attendance'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                            <div
                                                class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition">
                                            </div>
                                            <div
                                                class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot">
                                            </div>
                                        </div>
                                        <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Aktifkan
                                            Absen Pulang (Siswa)</div>
                                    </label>
                                    <p class="mt-1 ml-13 text-xs text-gray-500">Jika dinonaktifkan, siswa hanya perlu absen
                                        masuk (1x scan). Jika diaktifkan, siswa perlu absen masuk dan pulang (2x scan).</p>
                                </div>

                                <div>
                                    <label class="flex items-center cursor-pointer select-none">
                                        <div class="relative">
                                            <input type="checkbox" id="enable_checkout_teacher"
                                                name="enable_checkout_teacher" value="true" class="sr-only" {{ ($settings['enable_checkout_teacher'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                            <div
                                                class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition">
                                            </div>
                                            <div
                                                class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot">
                                            </div>
                                        </div>
                                        <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Aktifkan
                                            Absen Pulang (Karyawan/Guru)</div>
                                    </label>
                                    <p class="mt-1 ml-13 text-xs text-gray-500">Jika diaktifkan, Karyawan/Guru diwajibkan
                                        untuk menempelkan kartu kembali saat Jam Pulang.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Otomatisasi -->
                <div x-show="activeTab === 'automation'" style="display: none;"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" class="space-y-6">

                    <h3
                        class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2">
                        Konfigurasi Jadwal Otomatis (Scheduler)</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Proses Absensi
                                Harian (Auto Bolos/Alpha)</label>
                            <input type="time" name="schedule_process_daily"
                                value="{{ $settings['schedule_process_daily'] ?? '13:30' }}"
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500">Waktu sistem memproses siswa yang tidak hadir (Alpha) atau
                                tidak absen pulang (Bolos)</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Laporan Harian</label>
                            <input type="time" name="schedule_daily_report"
                                value="{{ $settings['schedule_daily_report'] ?? '08:15' }}"
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500">Waktu pengiriman rekap kehadiran ke grup kelas & wali
                                kelas</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Masa Berlaku Izin Sakit (Hari)</label>
                            <input type="number" name="sakit_max_days" min="1" max="30"
                                value="{{ $settings['sakit_max_days'] ?? '2' }}"
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500">Jumlah hari izin "Sakit" berlaku (Contoh: 2 hari berarti jika hari ini izin sakit, besok otomatis masih izin sakit bila tidak absen masuk).</p>
                        </div>


                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Masa Kadaluarsa Last Seen WA (Hari)</label>
                            <input type="number" name="last_seen_expiry_days" min="1" max="30"
                                value="{{ $settings['last_seen_expiry_days'] ?? '3' }}"
                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500">Batas masa aktif status last_seen WhatsApp secara global (Default: 3 hari = 72 jam)</p>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2 mt-8">
                        📢 Pengaturan Pengiriman Notifikasi Kehadiran
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- WhatsApp Notifications -->
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 space-y-4">
                            <h4 class="font-semibold text-sm text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2 flex items-center gap-1.5">
                                <i class="fab fa-whatsapp text-success-500"></i> Notifikasi WhatsApp
                            </h4>

                            <div>
                                <label class="flex items-center cursor-pointer select-none">
                                    <div class="relative">
                                        <input type="checkbox" id="notification_wa_siswa"
                                            name="notification_wa_siswa" value="true" class="sr-only" {{ ($settings['notification_wa_siswa'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                        <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                        <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                    </div>
                                    <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Notif WA Siswa</div>
                                </label>
                                <p class="mt-1 ml-13 text-xs text-gray-500">Aktifkan pengiriman notifikasi/alert WA ke nomor siswa.</p>
                            </div>

                            <div>
                                <label class="flex items-center cursor-pointer select-none">
                                    <div class="relative">
                                        <input type="checkbox" id="notification_wa_ortu"
                                            name="notification_wa_ortu" value="true" class="sr-only" {{ ($settings['notification_wa_ortu'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                        <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                        <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                    </div>
                                    <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Notif WA Ortu</div>
                                </label>
                                <p class="mt-1 ml-13 text-xs text-gray-500">Aktifkan pengiriman notifikasi/alert WA ke nomor orang tua / wali.</p>
                            </div>
                        </div>

                        <!-- Telegram Notifications -->
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 space-y-4">
                            <h4 class="font-semibold text-sm text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2 flex items-center gap-1.5">
                                <i class="fab fa-telegram-plane text-brand-500"></i> Notifikasi Telegram
                            </h4>

                            <div>
                                <label class="flex items-center cursor-pointer select-none">
                                    <div class="relative">
                                        <input type="checkbox" id="notification_tele_siswa"
                                            name="notification_tele_siswa" value="true" class="sr-only" {{ ($settings['notification_tele_siswa'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                        <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                        <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                    </div>
                                    <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Notif Telegram Siswa</div>
                                </label>
                                <p class="mt-1 ml-13 text-xs text-gray-500">Aktifkan pengiriman notifikasi/alert Telegram ke akun siswa.</p>
                            </div>

                            <div>
                                <label class="flex items-center cursor-pointer select-none">
                                    <div class="relative">
                                        <input type="checkbox" id="notification_tele_ortu"
                                            name="notification_tele_ortu" value="true" class="sr-only" {{ ($settings['notification_tele_ortu'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                        <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                        <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                    </div>
                                    <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Notif Telegram Ortu</div>
                                </label>
                                <p class="mt-1 ml-13 text-xs text-gray-500">Aktifkan pengiriman notifikasi/alert Telegram ke akun orang tua / wali.</p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2 mt-8">
                        🎂 Ucapan Selamat Ulang Tahun (Otomatis Pukul 00.01)
                    </h3>

                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                        <div class="mb-5">
                            <label class="flex items-center cursor-pointer select-none">
                                <div class="relative">
                                    <input type="checkbox" id="enable_birthday_greeting"
                                        name="enable_birthday_greeting" value="true" class="sr-only" {{ ($settings['enable_birthday_greeting'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                    <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                    <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                </div>
                                <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Aktifkan Ucapan Selamat Ulang Tahun via WhatsApp</div>
                            </label>
                            <p class="mt-1 ml-13 text-xs text-gray-500">Jika diaktifkan, sistem akan mengirimkan ucapan selamat ulang tahun ke Guru dan Siswa setiap pukul 00.01 berdasarkan tanggal lahir yang terdaftar.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Template Ucapan Ulang Tahun Siswa</label>
                                <textarea name="birthday_greeting_siswa" rows="5"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white text-sm"
                                    placeholder="🎂 Selamat Ulang Tahun, {nama}!&#10;&#10;Semoga panjang umur, sehat selalu, dan semakin berprestasi di sekolah. Teruslah semangat belajar ya! 🎉&#10;&#10;Salam hangat,&#10;{sekolah}">{{ $settings['birthday_greeting_siswa'] ?? '' }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Variabel: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{nama}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{sekolah}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{kelas}</code></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Template Ucapan Ulang Tahun Guru/Karyawan</label>
                                <textarea name="birthday_greeting_guru" rows="5"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white text-sm"
                                    placeholder="🎂 Selamat Ulang Tahun, Bapak/Ibu {nama}!&#10;&#10;Semoga panjang umur, sehat selalu, dan semakin sukses dalam mendidik generasi penerus bangsa. 🎉&#10;&#10;Salam hormat,&#10;{sekolah}">{{ $settings['birthday_greeting_guru'] ?? '' }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Variabel: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{nama}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{sekolah}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{nip}</code></p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Tab Lisensi Aplikasi -->
                @if(config('app.mode') === 'self_hosted' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()))
                <div x-show="activeTab === 'license'" style="display: none;"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" class="space-y-6">

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2">
                        <i class="fas fa-key text-brand-500 mr-2"></i> Konfigurasi Lisensi
                    </h3>

                    <div class="mb-6 rounded-lg border-l-4 border-warning bg-warning/10 p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-warning mt-1 mr-3"></i>
                            <div>
                                <h4 class="font-medium text-warning">Peringatan</h4>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Aplikasi berjalan dalam mode <span class="font-semibold">Self Hosted</span>. Harap masukkan <strong>License Key</strong> yang valid agar fitur-fitur utama dapat berfungsi. Lisensi divalidasi ke server pusat.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-6">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">License Key</label>
                                <input type="text" name="license_key"
                                    value="{{ config('app.license_key') }}"
                                    placeholder="XXXX-XXXX-XXXX-XXXX"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white font-mono tracking-wider">
                                <p class="mt-1 text-xs text-gray-500">Masukkan kode lisensi yang diberikan oleh Provider Anda.</p>
                            </div>
                            
                            @php
                                $licenseService = app(\App\Services\LicenseService::class);
                                $licenseStatus = $licenseService->validate();
                            @endphp
                            
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                                <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Status Lisensi Saat Ini</h4>
                                
                                @if($licenseStatus && $licenseStatus['valid'])
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">
                                            <i class="fas fa-check-circle mr-1"></i> Aktif
                                        </span>
                                    </div>
                                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <li><strong>Klien:</strong> {{ $licenseStatus['client_name'] ?? '-' }}</li>
                                        <li><strong>Berlaku s/d:</strong> {{ $licenseStatus['expired_at'] ?? 'Selamanya' }}</li>
                                        <li><strong>Max Sekolah:</strong> {{ ($licenseStatus['max_schools'] ?? 0) === 0 ? 'Unlimited' : $licenseStatus['max_schools'] }}</li>
                                        <li><strong>Max Siswa:</strong> {{ ($licenseStatus['max_students'] ?? 0) === 0 ? 'Unlimited' : $licenseStatus['max_students'] }}</li>
                                        <li><strong>Max Guru:</strong> {{ ($licenseStatus['max_teachers'] ?? 0) === 0 ? 'Unlimited' : $licenseStatus['max_teachers'] }}</li>
                                        <li><strong>Max Bot User:</strong> {{ ($licenseStatus['max_bot_users'] ?? 0) === 0 ? 'Unlimited' : $licenseStatus['max_bot_users'] }}</li>
                                    </ul>
                                @else
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex rounded-full bg-danger/10 px-3 py-1 text-xs font-medium text-danger">
                                            <i class="fas fa-times-circle mr-1"></i> Tidak Valid / Expired
                                        </span>
                                    </div>
                                    <p class="text-sm text-danger mt-1">{{ $licenseStatus['message'] ?? 'Lisensi gagal diverifikasi. Pastikan License Key benar.' }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Tab Lokasi & Geofencing (Mobile) -->
                <div x-show="activeTab === 'geofence'" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">

                    <div class="border-b border-gray-200 dark:border-gray-800 pb-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                    Pengaturan Lokasi & Geofencing Presensi Mobile
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Batasi absensi via aplikasi mobile Android agar hanya dapat dilakukan jika pengguna berada di dalam radius area sekolah.
                                </p>
                            </div>
                            <div class="flex items-center">
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" name="geofence_enabled" value="true" class="sr-only"
                                        {{ ($settings['geofence_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                    <div class="toggle-bg h-6 w-11 rounded-full bg-gray-200 transition-colors dark:bg-gray-700"></div>
                                    <div class="toggle-dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform"></div>
                                </label>
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Aktifkan Geofence
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Left: Inputs & GPS Helper -->
                        <div class="lg:col-span-5 space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Latitude Titik Sekolah <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" id="school_latitude" name="school_latitude"
                                        value="{{ $settings['school_latitude'] ?? '-6.175392' }}"
                                        placeholder="-6.175392"
                                        class="w-full rounded-lg border border-gray-200 bg-transparent pl-10 pr-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white text-sm font-mono">
                                    <i class="fas fa-map-pin absolute left-3.5 top-3.5 text-gray-400 text-xs"></i>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Longitude Titik Sekolah <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" id="school_longitude" name="school_longitude"
                                        value="{{ $settings['school_longitude'] ?? '106.827153' }}"
                                        placeholder="106.827153"
                                        class="w-full rounded-lg border border-gray-200 bg-transparent pl-10 pr-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white text-sm font-mono">
                                    <i class="fas fa-map-pin absolute left-3.5 top-3.5 text-gray-400 text-xs"></i>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Radius Toleransi Jarak (Meter) <span class="text-danger">*</span>
                                    </label>
                                    <span id="radius_display" class="inline-flex items-center rounded-full bg-brand-500/10 px-2.5 py-0.5 text-xs font-semibold text-brand-500">
                                        {{ $settings['geofence_radius'] ?? '100' }} m
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="number" id="geofence_radius" name="geofence_radius" min="10" max="5000" step="5"
                                        value="{{ $settings['geofence_radius'] ?? '100' }}"
                                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white text-sm">
                                </div>
                                <p class="mt-1.5 text-xs text-gray-500">
                                    Jarak maksimal (dalam meter) dari titik pusat sekolah yang diperbolehkan untuk melakukan absensi mobile.
                                </p>
                            </div>

                            <!-- Action buttons -->
                            <div class="pt-2 flex flex-col sm:flex-row gap-2">
                                <button type="button" id="btn-get-current-loc"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-700 transition shadow-sm">
                                    <i class="fas fa-crosshairs"></i> Ambil Lokasi Saya Saat Ini
                                </button>
                                <a id="btn-open-gmaps" href="https://maps.google.com/?q={{ $settings['school_latitude'] ?? '-6.175392' }},{{ $settings['school_longitude'] ?? '106.827153' }}" target="_blank"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                                    <i class="fas fa-external-link-alt"></i> Buka Google Maps
                                </a>
                            </div>

                            <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-3.5 dark:border-amber-900/30 dark:bg-amber-900/10">
                                <div class="flex gap-2.5 text-amber-800 dark:text-amber-300 text-xs leading-relaxed">
                                    <i class="fas fa-info-circle text-amber-600 dark:text-amber-400 mt-0.5"></i>
                                    <div>
                                        <strong>Petunjuk Peta:</strong> Anda dapat menggeser (drag) pin merah pada peta di samping atau mengklik area peta untuk menentukan titik koordinat sekolah secara presisi.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Interactive Leaflet Map -->
                        <div class="lg:col-span-7">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Visualisasi Area Radius Sekolah
                            </label>
                            <div id="geofence-map" class="w-full h-[380px] rounded-xl border border-gray-200 dark:border-gray-800 shadow-inner z-0"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800">
                    <button type="submit"
                        class="flex w-full md:w-auto items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-3 font-medium text-white hover:bg-brand-600 transition">
                        <i class="fas fa-save"></i> Simpan Pengaturan
                    </button>
                </div>
            </div>
        </div>
    </form>



    <style>
        /* Custom Toggle Switch Styles */
        input:checked~.toggle-bg {
            background-color: #3C50E0;
            /* brand-500 */
        }

        input:checked~.toggle-dot {
            transform: translateX(100%);
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Logo preview
        document.getElementById('logo-input')?.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('logo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Kop Surat preview
        document.getElementById('kop-input')?.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('kop-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Geofence Map Integration
        let map = null;
        let marker = null;
        let circle = null;

        window.initGeofenceMap = function() {
            const mapContainer = document.getElementById('geofence-map');
            if (!mapContainer) return;

            let lat = parseFloat(document.getElementById('school_latitude')?.value) || -6.175392;
            let lng = parseFloat(document.getElementById('school_longitude')?.value) || 106.827153;
            let radius = parseFloat(document.getElementById('geofence_radius')?.value) || 100;

            if (!map) {
                map = L.map('geofence-map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);

                // Custom Red Pin Icon
                const redIcon = L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                marker = L.marker([lat, lng], { draggable: true, icon: redIcon }).addTo(map);
                circle = L.circle([lat, lng], {
                    color: '#3C50E0',
                    fillColor: '#3C50E0',
                    fillOpacity: 0.25,
                    radius: radius
                }).addTo(map);

                marker.on('drag', function(e) {
                    const pos = e.latlng;
                    document.getElementById('school_latitude').value = pos.lat.toFixed(6);
                    document.getElementById('school_longitude').value = pos.lng.toFixed(6);
                    circle.setLatLng(pos);
                    updateGmapsLink(pos.lat, pos.lng);
                });

                map.on('click', function(e) {
                    const pos = e.latlng;
                    marker.setLatLng(pos);
                    circle.setLatLng(pos);
                    document.getElementById('school_latitude').value = pos.lat.toFixed(6);
                    document.getElementById('school_longitude').value = pos.lng.toFixed(6);
                    updateGmapsLink(pos.lat, pos.lng);
                });
            } else {
                map.invalidateSize();
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                circle.setLatLng([lat, lng]);
                circle.setRadius(radius);
            }
        };

        function updateGmapsLink(lat, lng) {
            const btn = document.getElementById('btn-open-gmaps');
            if (btn) {
                btn.href = `https://maps.google.com/?q=${lat},${lng}`;
            }
        }

        // Event listener for inputs
        document.getElementById('school_latitude')?.addEventListener('input', function() {
            let lat = parseFloat(this.value);
            let lng = parseFloat(document.getElementById('school_longitude')?.value);
            if (!isNaN(lat) && !isNaN(lng) && marker && circle) {
                marker.setLatLng([lat, lng]);
                circle.setLatLng([lat, lng]);
                map.panTo([lat, lng]);
                updateGmapsLink(lat, lng);
            }
        });

        document.getElementById('school_longitude')?.addEventListener('input', function() {
            let lat = parseFloat(document.getElementById('school_latitude')?.value);
            let lng = parseFloat(this.value);
            if (!isNaN(lat) && !isNaN(lng) && marker && circle) {
                marker.setLatLng([lat, lng]);
                circle.setLatLng([lat, lng]);
                map.panTo([lat, lng]);
                updateGmapsLink(lat, lng);
            }
        });

        document.getElementById('geofence_radius')?.addEventListener('input', function() {
            let r = parseFloat(this.value) || 100;
            const display = document.getElementById('radius_display');
            if (display) display.textContent = r + ' m';
            if (circle) {
                circle.setRadius(r);
            }
        });

        // GPS Current Location Button
        document.getElementById('btn-get-current-loc')?.addEventListener('click', function() {
            const btn = this;
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung deteksi lokasi (Geolocation).');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendeteksi Lokasi...';

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i> Ambil Lokasi Saya Saat Ini';

                    const lat = pos.coords.latitude.toFixed(6);
                    const lng = pos.coords.longitude.toFixed(6);

                    document.getElementById('school_latitude').value = lat;
                    document.getElementById('school_longitude').value = lng;

                    if (window.initGeofenceMap) {
                        window.initGeofenceMap();
                    }
                    updateGmapsLink(lat, lng);
                },
                function(err) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i> Ambil Lokasi Saya Saat Ini';
                    alert('Gagal mendapatkan lokasi GPS: ' + err.message + '. Pastikan izin akses lokasi aktif pada browser.');
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    </script>
@endpush