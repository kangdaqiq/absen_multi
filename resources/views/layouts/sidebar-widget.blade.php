<div class="mx-auto mb-10 w-full max-w-60 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-white border border-gray-200/80 p-2 shadow-theme-xs dark:border-gray-700 dark:bg-white">
        <img src="{{ asset('images/logo/logo-icon.svg') }}" alt="Logo Jagat Absen" class="h-6 w-6 object-contain" />
    </div>
    <h3 class="mb-1 text-sm font-bold text-gray-900 dark:text-white">
        App Jagat Absen
    </h3>
    <p class="mb-3.5 text-xs text-gray-500 dark:text-gray-400">
        Aplikasi mobile Android untuk presensi & Face ID.
    </p>
    <a href="{{ route('public.download.android') }}" target="_blank"
        class="flex items-center justify-center gap-1.5 p-2.5 font-semibold text-white rounded-xl bg-brand-500 text-xs shadow-theme-xs hover:bg-brand-600 transition"
        style="background-color: #4f6bed; color: #ffffff;">
        <i class="fas fa-download text-[11px]"></i>
        <span>Download APK</span>
    </a>
</div>
