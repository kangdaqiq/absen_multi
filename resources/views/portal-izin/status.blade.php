<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pengajuan #{{ $leave->code }} - {{ $leave->school->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-indigo-50/40 to-slate-100 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 min-h-full text-slate-800 dark:text-slate-100 flex flex-col justify-between p-4 sm:p-6 lg:p-8">

    <div class="max-w-xl mx-auto w-full my-auto">
        <!-- Status Header -->
        <div class="text-center mb-6">
            @if($leave->status === 'approved')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-success-500/10 text-success-500 shadow-sm mb-3 border border-success-500/20">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pengajuan Disetujui</h1>
                <p class="text-xs text-slate-500 mt-1">Data absensi telah otomatis diperbarui oleh sekolah.</p>
            @elseif($leave->status === 'rejected')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-error-500/10 text-error-500 shadow-sm mb-3 border border-error-500/20">
                    <i class="fas fa-times-circle text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pengajuan Ditolak</h1>
                <p class="text-xs text-slate-500 mt-1">Mohon hubungi Wali Kelas untuk informasi lebih lanjut.</p>
            @else
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-warning-500/10 text-warning-500 shadow-sm mb-3 border border-warning-500/20 animate-pulse">
                    <i class="fas fa-hourglass-half text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Menunggu Persetujuan</h1>
                <p class="text-xs text-slate-500 mt-1">Pengajuan telah diteruskan ke Wali Kelas untuk ditinjau.</p>
            @endif
        </div>

        @if(session('success'))
        <div class="mb-5 rounded-2xl bg-success-50 dark:bg-success-500/10 p-4 border border-success-200 dark:border-success-800/40 text-success-800 dark:text-success-300 text-sm flex items-center gap-3">
            <i class="fas fa-check-circle text-lg"></i>
            <div>{{ session('success') }}</div>
        </div>
        @endif

        <!-- Card Detail -->
        <div class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl rounded-3xl border border-slate-200/80 dark:border-gray-800 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
            <!-- Resi Header Bar -->
            <div class="bg-slate-50 dark:bg-gray-800/50 p-4 sm:p-5 border-b border-slate-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Nomor Resi / Kode</div>
                    <div class="font-mono font-bold text-base text-slate-800 dark:text-white tracking-wider">{{ $leave->code }}</div>
                </div>
                <div>
                    {!! $leave->status_badge !!}
                </div>
            </div>

            <div class="p-5 sm:p-6 space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-4 pb-3 border-b border-slate-100 dark:border-gray-800">
                    <div>
                        <span class="text-xs text-slate-400 block mb-0.5">Nama Siswa</span>
                        <strong class="text-slate-800 dark:text-white">{{ $leave->student->nama ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block mb-0.5">Kelas</span>
                        <strong class="text-brand-600 dark:text-brand-400">{{ $leave->student->kelas->nama_kelas ?? '-' }}</strong>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pb-3 border-b border-slate-100 dark:border-gray-800">
                    <div>
                        <span class="text-xs text-slate-400 block mb-0.5">Jenis Pengajuan</span>
                        <span class="font-bold uppercase text-slate-700 dark:text-slate-300">{{ $leave->jenis_label }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block mb-0.5">Durasi Izin</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ $leave->durasi_hari }} Hari</span>
                    </div>
                </div>

                <div class="pb-3 border-b border-slate-100 dark:border-gray-800">
                    <span class="text-xs text-slate-400 block mb-0.5">Rentang Tanggal</span>
                    <div class="font-semibold text-slate-800 dark:text-white">
                        <i class="far fa-calendar-alt text-brand-500 mr-1.5"></i>
                        {{ $leave->tanggal_mulai->translatedFormat('d F Y') }}
                        @if(!$leave->tanggal_mulai->isSameDay($leave->tanggal_selesai))
                            s/d {{ $leave->tanggal_selesai->translatedFormat('d F Y') }}
                        @endif
                    </div>
                </div>

                <div class="pb-3 border-b border-slate-100 dark:border-gray-800">
                    <span class="text-xs text-slate-400 block mb-0.5">Alasan / Keterangan</span>
                    <p class="text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-gray-800/40 p-3 rounded-xl border border-slate-100 dark:border-gray-800/60 text-xs leading-relaxed">
                        {{ $leave->keterangan }}
                    </p>
                </div>

                @if($leave->bukti_foto)
                <div class="pb-3 border-b border-slate-100 dark:border-gray-800">
                    <span class="text-xs text-slate-400 block mb-1.5">Bukti Foto / Surat Dokter</span>
                    @php
                        $isPdf = str_ends_with(strtolower($leave->bukti_foto), '.pdf');
                    @endphp
                    @if($isPdf)
                        <a href="{{ asset('storage/' . $leave->bukti_foto) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 dark:bg-gray-800 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:bg-slate-200 transition">
                            <i class="fas fa-file-pdf text-error-500 text-base"></i> Buka Dokumen PDF
                        </a>
                    @else
                        <a href="{{ asset('storage/' . $leave->bukti_foto) }}" target="_blank" class="block group">
                            <img src="{{ asset('storage/' . $leave->bukti_foto) }}" alt="Surat Izin" class="max-h-48 rounded-xl object-contain border border-slate-200 dark:border-gray-700 group-hover:opacity-90 transition">
                        </a>
                    @endif
                </div>
                @endif

                @if($leave->status === 'rejected' && $leave->rejected_reason)
                <div class="p-3.5 rounded-xl bg-error-50 dark:bg-error-500/10 border border-error-200 dark:border-error-800/40">
                    <span class="text-xs font-bold text-error-600 dark:text-error-400 block mb-1">
                        <i class="fas fa-exclamation-circle mr-1"></i> Catatan Penolakan:
                    </span>
                    <p class="text-xs text-error-700 dark:text-error-300">{{ $leave->rejected_reason }}</p>
                </div>
                @endif

                @if($leave->status === 'approved' && $leave->approver)
                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between pt-1">
                    <span>Disetujui oleh: <strong>{{ $leave->approver->full_name }}</strong></span>
                    <span>{{ $leave->approved_at?->translatedFormat('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>

            <!-- Action footer -->
            <div class="p-4 sm:p-5 bg-slate-50 dark:bg-gray-800/50 border-t border-slate-100 dark:border-gray-800 flex flex-col sm:flex-row gap-3">
                <a href="{{ route('portal-izin.index') }}" class="flex-1 py-3 px-4 rounded-xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 font-semibold text-xs text-center text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition">
                    <i class="fas fa-plus mr-1.5"></i> Buat Pengajuan Baru
                </a>
                <button onclick="window.print()" class="py-3 px-5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-semibold text-xs text-center transition">
                    <i class="fas fa-print mr-1.5"></i> Cetak / Simpan
                </button>
            </div>
        </div>

        <div class="text-center mt-6 text-xs text-slate-400">
            Sistem Absensi & Perizinan Sekolah &copy; {{ date('Y') }}
        </div>
    </div>

</body>
</html>
