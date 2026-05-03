@extends('layouts.app')

@section('title', 'Backup & Restore Data')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-database text-brand-500 mr-2"></i> Backup & Restore Data Sekolah
    </h2>
</div>

{{-- Flash Messages --}}
@if(session('success'))
<div id="alertSuccess" class="mb-5 flex w-full items-start gap-3 rounded-md border border-success/30 bg-success/10 px-5 py-4 shadow-sm">
    <span class="mt-0.5 text-success"><i class="fas fa-check-circle text-xl"></i></span>
    <div class="flex-1">
        <h5 class="font-semibold text-success">Berhasil!</h5>
        <p class="text-sm text-success/90">{{ session('success') }}</p>
    </div>
    <button onclick="document.getElementById('alertSuccess').remove()" class="ml-auto text-success/70 hover:text-success">&times;</button>
</div>
@endif

@if(session('error'))
<div id="alertError" class="mb-5 flex w-full items-start gap-3 rounded-md border border-danger/30 bg-danger/10 px-5 py-4 shadow-sm">
    <span class="mt-0.5 text-danger"><i class="fas fa-exclamation-circle text-xl"></i></span>
    <div class="flex-1">
        <h5 class="font-semibold text-danger">Restore Gagal!</h5>
        <p class="text-sm text-danger/90">{{ session('error') }}</p>
    </div>
    <button onclick="document.getElementById('alertError').remove()" class="ml-auto text-danger/70 hover:text-danger">&times;</button>
</div>
@endif

@if($errors->any())
<div id="alertValidation" class="mb-5 flex w-full items-start gap-3 rounded-md border border-danger/30 bg-danger/10 px-5 py-4 shadow-sm">
    <span class="mt-0.5 text-danger"><i class="fas fa-exclamation-triangle text-xl"></i></span>
    <div class="flex-1">
        <h5 class="font-semibold text-danger">Validasi Gagal!</h5>
        <ul class="mt-1 list-disc list-inside text-sm text-danger/90">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    <button onclick="document.getElementById('alertValidation').remove()" class="ml-auto text-danger/70 hover:text-danger">&times;</button>
</div>
@endif

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <!-- Export / Backup -->
    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
            <h3 class="font-medium text-black dark:text-white flex items-center gap-2">
                <i class="fas fa-download text-brand-500"></i> Download Backup (Export)
            </h3>
        </div>
        
        <div class="p-6.5">
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Unduh seluruh data sekolah dalam format JSON.</p>

            <form action="{{ route('backup.download') }}" method="GET">
                @if(auth()->user()->isSuperAdmin())
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Pilih Sekolah <span class="text-meta-1">*</span></label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="school_id" required class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                                <option value="">-- Pilih Sekolah --</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }} ({{ $school->code }})</option>
                                @endforeach
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                    </div>
                @else
                    <div class="mb-6 flex w-full border-l-6 border-warning bg-warning/10 px-7 py-3 shadow-md">
                        <div class="w-full">
                            <h5 class="font-medium text-[#9D5425]"><i class="fas fa-school mr-1"></i> Sekolah: <strong>{{ auth()->user()->school->name ?? '-' }}</strong></h5>
                        </div>
                    </div>
                @endif

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded bg-brand-500 p-3 font-medium text-white hover:bg-opacity-90 transition">
                    <i class="fas fa-download"></i> Download Backup
                </button>
            </form>
        </div>
    </div>

    <!-- Import / Restore -->
    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark relative overflow-hidden">
        <div class="absolute top-0 left-0 h-1 w-full bg-danger"></div>
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
            <h3 class="font-medium text-danger flex items-center gap-2">
                <i class="fas fa-upload"></i> Restore Data (Import)
            </h3>
        </div>
        
        <div class="p-6.5">
            <div class="mb-6 flex w-full border-l-6 border-danger bg-danger/10 px-5 py-4 shadow-md">
                <div class="w-full">
                    <h5 class="mb-1 font-semibold text-[#B45454]">PERHATIAN!</h5>
                    <p class="text-sm text-[#B45454]">Fitur ini akan <u class="font-bold">MENGHAPUS</u> seluruh data sekolah yang dipilih dan menggantinya dengan data dari file backup.</p>
                </div>
            </div>

            <form action="{{ route('backup.restore') }}" method="POST" enctype="multipart/form-data" id="restoreForm">
                @csrf
                
                @if(auth()->user()->isSuperAdmin())
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Pilih Sekolah Target (Data akan ditimpa) <span class="text-meta-1">*</span></label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="school_id" required class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                                <option value="">-- Pilih Sekolah --</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }} ({{ $school->code }})</option>
                                @endforeach
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                    </div>
                @endif

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Pilih File Backup (.json) <span class="text-meta-1">*</span></label>
                    <input type="file" name="backup_file" accept=".json,.txt" required class="w-full cursor-pointer rounded-lg border-[1.5px] border-stroke bg-transparent font-medium outline-none transition file:mr-5 file:border-collapse file:cursor-pointer file:border-0 file:border-r file:border-solid file:border-stroke file:bg-whiter file:py-3 file:px-5 file:hover:bg-brand-500 file:hover:bg-opacity-10 focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:file:border-form-strokedark dark:file:bg-white/30 dark:file:text-white dark:focus:border-brand-500" />
                </div>

                <div class="mb-5.5 flex items-center gap-3">
                    <label for="confirmCheck" class="flex cursor-pointer select-none items-center">
                        <div class="relative">
                            <input type="checkbox" id="confirmCheck" name="confirmation" required class="sr-only" />
                            <div class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark">
                                <span class="opacity-0">
                                    <i class="fas fa-check text-xs text-brand-500 dark:text-white"></i>
                                </span>
                            </div>
                        </div>
                        <span class="text-sm">Saya mengerti bahwa data sekolah yang dipilih akan dihapus permanen.</span>
                    </label>
                </div>

                <button type="submit" id="restoreBtn" class="flex w-full items-center justify-center gap-2 rounded bg-danger p-3 font-medium text-white hover:bg-opacity-90 transition">
                    <i class="fas fa-upload"></i> Restore Database
                </button>
            </form>

            <!-- Progress Bar Container -->
            <div id="progressContainer" class="mt-6" style="display:none;">
                <div class="mb-2 flex justify-between">
                    <span class="text-sm font-semibold text-black dark:text-white">Memulihkan Data... Harap Tunggu</span>
                    <span id="progressText" class="text-sm font-semibold text-black dark:text-white">0%</span>
                </div>
                <div class="relative h-2.5 w-full rounded-full bg-stroke dark:bg-strokedark">
                    <div id="progressBar" class="absolute left-0 h-full rounded-full bg-danger transition-all duration-300" style="width: 0%"></div>
                </div>
                <p class="mt-2 text-xs italic text-danger">Jangan tutup halaman ini selama proses berlangsung.</p>
            </div>
        </div>
    </div>
</div>

<style>
    input:checked ~ .box {
        border-color: #3C50E0;
        background-color: #3C50E0;
    }
    input:checked ~ .box span {
        opacity: 1;
        color: white;
    }
    .dark input:checked ~ .box {
        border-color: #3C50E0;
        background-color: #3C50E0;
    }
</style>

<script>
    // Jika ada session error atau validation error, pastikan tombol restore tampil & progress disembunyikan
    @if(session('error') || $errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('restoreBtn');
        var prog = document.getElementById('progressContainer');
        if (btn) btn.style.display = '';
        if (prog) prog.style.display = 'none';
    });
    @endif

    document.getElementById('restoreForm').addEventListener('submit', function(e) {
        
        if (!confirm("APAKAH ANDA YAKIN?\n\nSemua data sekolah saat ini akan DIHAPUS dan digantikan dengan data backup.\nTindakan ini tidak dapat dibatalkan.")) {
            e.preventDefault();
            return false;
        }

        // Hide button, show progress
        document.getElementById('restoreBtn').style.display = 'none';
        document.getElementById('progressContainer').style.display = 'block';

        let progressBar = document.getElementById('progressBar');
        let progressText = document.getElementById('progressText');
        
        let progress = 0;
        let duration = 40000; // Asumsi maksimal 40 detik
        let interval = 200;
        let step = 100 / (duration / interval);

        let timer = setInterval(function() {
            // Easing logaritmik palsu (perlambat di akhir)
            if (progress >= 85) {
                step = 0.05; 
            }
            if (progress >= 99) {
                progress = 99;
                clearInterval(timer);
            } else {
                progress += step;
            }
            
            let displayProgress = Math.floor(progress);
            progressBar.style.width = displayProgress + '%';
            progressText.innerText = displayProgress + '%';
        }, interval);

        // Biarkan form disubmit secara normal oleh browser!
    });
</script>
@endsection