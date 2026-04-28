@extends('layouts.app')

@section('title', 'Broadcast WhatsApp')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-bullhorn text-brand-500 mr-2"></i> Broadcast WhatsApp
    </h2>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
    <div class="xl:col-span-3">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h6 class="font-semibold text-gray-800 dark:text-white/90">Kirim Pesan ke Siswa</h6>
            </div>
            
            <div class="p-6">
                @if(session('success'))
                    <div class="mb-6 flex w-full border-l-6 border-success bg-success-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
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
                    <div class="mb-6 flex w-full border-l-6 border-error bg-error-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
                        <div class="mr-5 flex h-9 w-full max-w-[36px] items-center justify-center rounded-lg bg-error text-white">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="w-full">
                            <h5 class="mb-1 font-semibold text-[#B45454] dark:text-[#F87171]">Gagal</h5>
                            <p class="text-[#B45454] dark:text-[#F87171]">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <form action="{{ route('broadcast.send') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengirim pesan ini?');">
                    @csrf

                    <div class="mb-6">
                        <label class="mb-4 block text-sm font-semibold text-gray-800 dark:text-white/90">Target Penerima</label>
                        
                        <div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-500/20 dark:bg-brand-500/5">
                            <label class="flex items-center cursor-pointer select-none">
                                <div class="relative">
                                    <input type="checkbox" id="checkAll" class="sr-only">
                                    <div class="box mr-3 flex h-5 w-5 items-center justify-center rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 transition">
                                        <span class="opacity-0">
                                            <i class="fas fa-check text-xs text-brand-500"></i>
                                        </span>
                                    </div>
                                </div>
                                <span class="font-bold text-brand-500">📢 PILIH SEMUA KELAS</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 px-2">
                            @foreach($kelas as $k)
                                <label class="flex items-center cursor-pointer select-none">
                                    <div class="relative">
                                        <input type="checkbox" name="target_class_ids[]" value="{{ $k->id }}" id="kelas_{{ $k->id }}" class="class-checkbox sr-only">
                                        <div class="box mr-3 flex h-5 w-5 items-center justify-center rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 transition">
                                            <span class="opacity-0">
                                                <i class="fas fa-check text-xs text-brand-500"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $k->nama_kelas }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">* Pilih minimal satu kelas untuk dikirim pesan.</p>
                    </div>

                    <div class="mb-6 border-t border-gray-200 dark:border-gray-800 pt-6">
                        <label for="message" class="mb-3 block text-sm font-semibold text-gray-800 dark:text-white/90">Isi Pesan</label>
                        <textarea name="message" id="message" rows="6" required placeholder="Tulis pengumuman di sini..." class="w-full rounded-xl border border-gray-200 bg-transparent px-5 py-3 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:focus:border-brand-500 text-gray-800 dark:text-white/90 transition shadow-theme-xs"></textarea>
                        
                        <div class="mt-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                            <ul class="flex flex-col gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <li class="flex items-start gap-2"><i class="fas fa-info-circle mt-0.5 text-brand-500"></i> Pesan akan otomatis diawali dengan "📢 PENGUMUMAN SEKOLAH" dan sapaan nama siswa.</li>
                                <li class="flex items-start gap-2"><i class="fas fa-info-circle mt-0.5 text-brand-500"></i> Pesan akan otomatis diakhiri dengan signature admin.</li>
                            </ul>
                        </div>
                    </div>

                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3 font-medium text-white hover:bg-brand-600 transition">
                        <i class="fas fa-paper-plane"></i> Kirim Broadcast
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="xl:col-span-1">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h6 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-warning-500"></i> Tips Penggunaan
                </h6>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Gunakan fitur ini untuk memberikan informasi penting kepada siswa dan orang tua. Pesan yang dikirim melalui broadcast tidak dapat dibatalkan.
                </p>
                <div class="rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-500">
                    <strong>Penting:</strong> Pastikan perangkat WhatsApp dalam keadaan aktif (terkoneksi) sebelum mengirim broadcast.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Checkbox Logic */
    input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
        background-color: #3C50E0;
    }
    input[type="checkbox"]:checked ~ .box span {
        opacity: 1;
        color: white;
    }
    .dark input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
        background-color: #3C50E0;
    }
</style>
@endsection

@push('scripts')
    <script>
        document.getElementById('checkAll').addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('.class-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });

        var classCheckboxes = document.querySelectorAll('.class-checkbox');
        classCheckboxes.forEach(function (box) {
            box.addEventListener('change', function () {
                var allChecked = document.querySelectorAll('.class-checkbox:checked').length === classCheckboxes.length;
                document.getElementById('checkAll').checked = allChecked;
            });
        });
    </script>
@endpush