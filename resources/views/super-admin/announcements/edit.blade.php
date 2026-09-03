@extends('layouts.app')

@section('title', 'Edit Pengumuman & Update Rilis')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-edit text-brand-500 mr-2"></i> Edit Pengumuman / Update Rilis
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Ubah isi pengumuman atau pengaturan pop-up dashboard.
        </p>
    </div>
    <a href="{{ route('super-admin.announcements.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <form action="{{ route('super-admin.announcements.update', $announcement) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <!-- Tipe Pengumuman -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">
                            Kategori / Tipe <span class="text-error-500">*</span>
                        </label>
                        <select name="type" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="release" {{ old('type', $announcement->type) === 'release' ? 'selected' : '' }}>🚀 Update Rilis (New Version)</option>
                            <option value="feature" {{ old('type', $announcement->type) === 'feature' ? 'selected' : '' }}>💡 Fitur Baru</option>
                            <option value="info" {{ old('type', $announcement->type) === 'info' ? 'selected' : '' }}>📢 Informasi Umum</option>
                            <option value="warning" {{ old('type', $announcement->type) === 'warning' ? 'selected' : '' }}>⚠️ Pemberitahuan Penting</option>
                            <option value="maintenance" {{ old('type', $announcement->type) === 'maintenance' ? 'selected' : '' }}>🛠️ Pemeliharaan Sistem</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tag Versi -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">
                            Tag Versi <span class="text-xs text-gray-400 font-normal">(Opsional, misal: v2.5.0)</span>
                        </label>
                        <input type="text" name="version" value="{{ old('version', $announcement->version) }}" placeholder="Contoh: v2.5.0" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        @error('version')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Judul Pengumuman -->
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">
                        Judul Pengumuman / Update <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $announcement->title) }}" placeholder="Contoh: Pembaruan Sistem: Fitur Rekap Absen & Broadcast Baru" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white @error('title') border-error-500 @enderror" />
                    @error('title')
                        <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Isi Pengumuman -->
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-800 dark:text-white/90">
                            Isi Pengumuman / Catatan Rilis <span class="text-error-500">*</span>
                        </label>
                        <span class="text-xs text-gray-400">Mendukung baris baru, emoji & bullet point</span>
                    </div>
                    <textarea name="content" rows="7" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 font-mono text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white @error('content') border-error-500 @enderror">{{ old('content', $announcement->content) }}</textarea>
                    @error('content')
                        <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Button Optional -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700/60">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Link Aksi / Panduan (Opsional)
                        </label>
                        <input type="url" name="action_url" value="{{ old('action_url', $announcement->action_url) }}" placeholder="https://..." class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-800 outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Label Tombol Aksi (Opsional)
                        </label>
                        <input type="text" name="action_text" value="{{ old('action_text', $announcement->action_text) }}" placeholder="Contoh: Baca Panduan / Buka Fitur" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-800 outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                </div>

                <!-- Toggles -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_popup" value="1" {{ old('is_popup', $announcement->is_popup) ? 'checked' : '' }} class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-white">Tampilkan Pop-up Modal saat Login</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Otomatis muncul pop up di dashboard admin</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer sm:ml-auto">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $announcement->is_active) ? 'checked' : '' }} class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-white">Status Aktif</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Dapat dilihat oleh admin</p>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('super-admin.announcements.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <i class="fas fa-save"></i> Perbarui Pengumuman
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Box Sidebar -->
    <div class="lg:col-span-1">
        <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5 dark:border-brand-500/20 dark:bg-brand-500/5">
            <h4 class="font-bold text-gray-800 dark:text-white mb-2 flex items-center gap-2 text-base">
                <i class="fas fa-info-circle text-brand-500"></i> Informasi Pop-up
            </h4>
            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                Jika opsi <strong>Tampilkan Pop-up Modal</strong> dicentang, setiap Admin Sekolah yang login akan otomatis melihat jendela pop-up ini hingga mereka memilih "Jangan tampilkan lagi".
            </p>
            <div class="rounded-lg bg-white p-3 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <div>Dibuat: {{ $announcement->created_at->format('d M Y H:i') }}</div>
                <div>Terakhir diubah: {{ $announcement->updated_at->format('d M Y H:i') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
