@extends('layouts.app')

@section('title', 'Pengaturan Telegram Bot')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Pengaturan Telegram Bot
        </h2>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/15 dark:text-success-500 flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-500 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <!-- Form Konfigurasi (2 Kolom) -->
        <div class="xl:col-span-2 space-y-6">
            <form action="{{ route('settings.telegram.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark p-6 space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-3 flex items-center gap-2">
                        <i class="fab fa-telegram text-blue-500"></i> Konfigurasi Notifikasi Telegram
                    </h3>

                    <!-- Status Enable/Disable -->
                    <div class="rounded-lg border border-gray-100 p-4 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <label for="telegram_enabled" class="font-medium text-gray-800 dark:text-white/90 text-sm">Aktifkan Notifikasi Telegram</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Kirim pemberitahuan kehadiran/absen dan auto-bolos langsung ke Telegram penerima yang terdaftar.</p>
                            </div>
                            <label class="flex items-center cursor-pointer select-none">
                                <div class="relative">
                                    <input type="checkbox" id="telegram_enabled" name="telegram_enabled" value="true" class="sr-only" 
                                        {{ ($settings['telegram_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                    <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                    <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Token Input -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token Bot Telegram</label>
                        <input type="text" name="telegram_bot_token"
                            value="{{ $settings['telegram_bot_token'] ?? '' }}"
                            placeholder="Contoh: 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white text-sm transition">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Token bot didapatkan dari percakapan dengan <strong>@BotFather</strong> di Telegram.</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-150 dark:border-gray-800">
                        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition flex items-center gap-2">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Panduan Pembuatan (1 Kolom) -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark p-6">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-3 mb-4 flex items-center gap-2">
                    <i class="fas fa-book-open text-brand-500"></i> Panduan Pembuatan Bot
                </h3>

                <div class="space-y-5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    <!-- Langkah 1 -->
                    <div>
                        <h4 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-500">1</span>
                            Buka @BotFather
                        </h4>
                        <p class="text-xs ml-7">Buka aplikasi Telegram Anda, cari akun resmi <a href="https://t.me/BotFather" target="_blank" class="text-brand-500 hover:underline">@BotFather</a>, lalu klik tombol <strong>Start / Mulai</strong>.</p>
                    </div>

                    <!-- Langkah 2 -->
                    <div>
                        <h4 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-500">2</span>
                            Buat Bot Baru
                        </h4>
                        <p class="text-xs ml-7">Kirim pesan perintah <code>/newbot</code>. BotFather akan memandu Anda:</p>
                        <ul class="list-disc list-inside text-xs ml-7 mt-1.5 space-y-1">
                            <li>Masukkan <strong>Nama Bot</strong> (Contoh: <em>Absensi Sekolah Bot</em>).</li>
                            <li>Masukkan <strong>Username Bot</strong> yang berakhiran kata <code>_bot</code> (Contoh: <em>absen_sekolah_bot</em>).</li>
                        </ul>
                    </div>

                    <!-- Langkah 3 -->
                    <div>
                        <h4 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-500">3</span>
                            Salin Token API
                        </h4>
                        <p class="text-xs ml-7">Setelah selesai, BotFather akan mengirimkan pesan berisi token berbentuk:<br>
                        <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs select-all text-gray-800 dark:text-gray-300 font-mono mt-1 block">123456789:ABCdefGhIJK...</code><br>
                        Salin token ini lalu masukkan ke kolom <strong>Token Bot Telegram</strong> di sebelah kiri.</p>
                    </div>

                    <!-- Langkah 4 -->
                    <div>
                        <h4 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-500">4</span>
                            Mulai Bot Anda
                        </h4>
                        <p class="text-xs ml-7">Cari username bot baru Anda di Telegram, lalu klik <strong>Start / Mulai</strong>. Pengguna (Siswa/Ortu/Guru) juga harus melakukan hal yang sama agar Bot diizinkan mengirim pesan.</p>
                    </div>

                    <!-- Langkah 5 -->
                    <div>
                        <h4 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-500">5</span>
                            Dapatkan Chat ID
                        </h4>
                        <p class="text-xs ml-7">Gunakan bot publik seperti <a href="https://t.me/getmyid_bot" target="_blank" class="text-brand-500 hover:underline">@getmyid_bot</a> untuk mengetahui Chat ID Anda sendiri, lalu input angka tersebut di kolom Chat ID masing-masing profil pengguna.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .toggle-bg {
            background-color: #e5e7eb;
        }
        input:checked ~ .toggle-bg {
            background-color: #3b82f6; /* brand-500 */
        }
        .toggle-dot {
            left: 0.25rem;
            top: 0.25rem;
        }
        input:checked ~ .toggle-dot {
            transform: translateX(1rem);
        }
        .dark .toggle-bg {
            background-color: #4b5563;
        }
        .dark input:checked ~ .toggle-bg {
            background-color: #3b82f6;
        }
    </style>
@endsection
