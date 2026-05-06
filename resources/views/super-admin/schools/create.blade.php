@extends('layouts.app')

@section('title', 'Tambah Sekolah')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Tambah Sekolah Baru
    </h2>
    <a href="{{ route('super-admin.schools.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-gray-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="grid grid-cols-1 gap-9 sm:grid-cols-3">
    <div class="flex flex-col gap-9 sm:col-span-2">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">
                    Informasi Sekolah
                </h3>
            </div>
            
            <div class="p-6.5">
                <form action="{{ route('super-admin.schools.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Nama Sekolah / Instansi <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('name') border-danger @enderror" />
                        @error('name')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Tipe Tenant <span class="text-meta-1">*</span>
                        </label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="type" required class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('type') border-danger @enderror">
                                <option value="school" {{ old('type') == 'school' ? 'selected' : '' }}>Sekolah (Siswa, Kelas, Jadwal)</option>
                                <option value="office" {{ old('type') == 'office' ? 'selected' : '' }}>Perkantoran (Hanya Karyawan)</option>
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                        @error('type')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Kode Sekolah
                        </label>
                        <input type="text" value="[Dibuat Otomatis]" readonly disabled class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-medium text-black outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-meta-4 dark:text-white dark:focus:border-brand-500" />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Kode unik akan di-generate secara otomatis oleh sistem.</p>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Alamat
                        </label>
                        <textarea name="address" rows="3" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('address') border-danger @enderror">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('phone') border-danger @enderror" />
                            @error('phone')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('email') border-danger @enderror" />
                            @error('email')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            <i class="fas fa-headset text-brand-500 mr-1"></i> Nomor Operator / PIC Sekolah
                        </label>
                        <div class="relative">
                            <span class="absolute left-4.5 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-phone"></i>
                            </span>
                            <input type="text" name="operator_phone" value="{{ old('operator_phone') }}" placeholder="Contoh: 08123456789" class="w-full rounded border border-stroke bg-transparent py-3 pl-12 pr-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('operator_phone') border-danger @enderror" />
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Nomor WhatsApp operator/PIC yang dapat dihubungi untuk koordinasi teknis sekolah ini.</p>
                        @error('operator_phone')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Logo Sekolah</label>
                        <input type="file" name="logo" accept="image/*" class="w-full cursor-pointer rounded-lg border-[1.5px] border-stroke bg-transparent font-medium outline-none transition file:mr-5 file:border-collapse file:cursor-pointer file:border-0 file:border-r file:border-solid file:border-stroke file:bg-whiter file:py-3 file:px-5 file:hover:bg-brand-500 file:hover:bg-opacity-10 focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:file:border-form-strokedark dark:file:bg-white/30 dark:file:text-white dark:focus:border-brand-500 @error('logo') border-danger @enderror" />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Format: JPG, PNG. Maksimal 10MB</p>
                        @error('logo')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Package Selection -->
                    <div class="mb-5.5 rounded-lg border border-stroke p-5 bg-gray-50 dark:border-strokedark dark:bg-boxdark-2">
                        <h4 class="mb-4 font-semibold text-black dark:text-white">
                            <i class="fas fa-box text-brand-500 mr-2"></i> Paket Langganan
                        </h4>
                        
                        <div class="mb-4.5">
                            <label class="mb-2.5 block text-black dark:text-white">Pilih Paket</label>
                            <div class="relative z-20 bg-white dark:bg-form-input">
                                <select name="package_id" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                                    <option value="">-- Tanpa Paket (Gunakan Pengaturan Manual) --</option>
                                    @foreach($packages as $pkg)
                                        <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                                            {{ $pkg->name }} - Rp {{ number_format($pkg->price_monthly, 0, ',', '.') }}/Bulan
                                        </option>
                                    @endforeach
                                </select>
                                <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                    <i class="fas fa-chevron-down text-sm"></i>
                                </span>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Pilih paket untuk otomatis mengatur limit dan fitur (WA & Bot).</p>
                        </div>

                        <div class="mb-4.5">
                            <label class="mb-2.5 block text-black dark:text-white">Siklus Penagihan</label>
                            <div class="flex flex-wrap items-center gap-5">
                                <label class="flex cursor-pointer select-none items-center">
                                    <div class="relative">
                                        <input type="radio" name="billing_cycle" value="monthly" {{ old('billing_cycle', 'monthly') == 'monthly' ? 'checked' : '' }} class="sr-only" />
                                        <div class="box mr-2 flex h-5 w-5 items-center justify-center rounded-full border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                            <span class="h-2.5 w-2.5 rounded-full bg-transparent"></span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium text-black dark:text-white">Bulanan (Monthly)</span>
                                </label>
                                
                                <label class="flex cursor-pointer select-none items-center">
                                    <div class="relative">
                                        <input type="radio" name="billing_cycle" value="yearly" {{ old('billing_cycle') == 'yearly' ? 'checked' : '' }} class="sr-only" />
                                        <div class="box mr-2 flex h-5 w-5 items-center justify-center rounded-full border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                            <span class="h-2.5 w-2.5 rounded-full bg-transparent"></span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium text-black dark:text-white">Tahunan (Yearly)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <!-- End Package Selection -->

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Limit Siswa (Quota)</label>
                            <input type="number" id="input_student_limit" name="student_limit" value="{{ old('student_limit') }}" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 disabled:bg-gray-2 disabled:cursor-not-allowed dark:disabled:bg-meta-4 @error('student_limit') border-danger @enderror" />
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Biarkan kosong atau 0 untuk Unlimited.</p>
                            @error('student_limit')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Limit Guru/Karyawan</label>
                            <input type="number" id="input_teacher_limit" name="teacher_limit" value="{{ old('teacher_limit') }}" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 disabled:bg-gray-2 disabled:cursor-not-allowed dark:disabled:bg-meta-4 @error('teacher_limit') border-danger @enderror" />
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Biarkan kosong atau 0 untuk Unlimited.</p>
                            @error('teacher_limit')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Limit Guru Pengguna Bot WA</label>
                            <input type="number" id="input_bot_user_limit" name="bot_user_limit" value="{{ old('bot_user_limit') }}" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 disabled:bg-gray-2 disabled:cursor-not-allowed dark:disabled:bg-meta-4 @error('bot_user_limit') border-danger @enderror" />
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Biarkan kosong atau 0 untuk Unlimited.</p>
                            @error('bot_user_limit')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-5.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            <i class="fas fa-history text-warning mr-1"></i> Kuota Retensi History Absen
                        </label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select id="input_history_quota" name="history_quota_months" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 disabled:bg-gray-2 disabled:cursor-not-allowed dark:disabled:bg-meta-4 @error('history_quota_months') border-danger @enderror">
                                <option value="">-- Tidak Terbatas (Simpan Selamanya) --</option>
                                @foreach([3 => '3 Bulan', 6 => '6 Bulan', 9 => '9 Bulan', 12 => '1 Tahun (12 Bulan)', 24 => '2 Tahun (24 Bulan)', 36 => '3 Tahun (36 Bulan)'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('history_quota_months') == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                        <div class="mt-2 rounded-md bg-warning/10 p-3 text-sm text-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Data absen yang lebih lama dari kuota ini akan <strong>dihapus otomatis</strong> setiap malam. Pilih "Tidak Terbatas" untuk menonaktifkan auto-delete.
                        </div>
                        @error('history_quota_months')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5.5 flex flex-col gap-3">
                        <label for="is_active" class="flex cursor-pointer select-none items-center">
                            <div class="relative">
                                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="sr-only" />
                                <div class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                    <span class="opacity-0">
                                        <i class="fas fa-check text-xs text-brand-500"></i>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">Aktifkan Sekolah</span>
                        </label>

                        <label for="wa_enabled" class="flex cursor-pointer select-none items-center">
                            <div class="relative">
                                <input type="checkbox" id="wa_enabled" name="wa_enabled" value="1" {{ old('wa_enabled', true) ? 'checked' : '' }} class="sr-only" />
                                <div id="wa_enabled_box" class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                    <span class="opacity-0">
                                        <i class="fas fa-check text-xs text-brand-500"></i>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">Aktifkan Notifikasi WhatsApp</span>
                        </label>
                        
                        <label for="bot_enabled" class="flex cursor-pointer select-none items-center">
                            <div class="relative">
                                <input type="checkbox" id="bot_enabled" name="bot_enabled" value="1" {{ old('bot_enabled', true) ? 'checked' : '' }} class="sr-only" />
                                <div id="bot_enabled_box" class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                    <span class="opacity-0">
                                        <i class="fas fa-check text-xs text-brand-500"></i>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">Aktifkan Bot WhatsApp Interaktif</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4.5 border-t border-stroke pt-5 dark:border-strokedark">
                        <a href="{{ route('super-admin.schools.index') }}" class="flex justify-center rounded border border-stroke px-6 py-3 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
                            Batal
                        </a>
                        <button type="submit" class="flex justify-center rounded bg-brand-500 px-6 py-3 font-medium text-white hover:bg-opacity-90 transition">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="flex flex-col gap-9 sm:col-span-1">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">
                    Informasi
                </h3>
            </div>
            <div class="p-6.5 space-y-6">
                <div>
                    <h5 class="mb-2 font-semibold text-black dark:text-white">
                        <i class="fas fa-info-circle text-info mr-2"></i> Kode Sekolah
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kode unik yang akan digunakan sebagai identifier sekolah. Pastikan kode mudah diingat dan tidak sama dengan sekolah lain.</p>
                </div>
                
                <div>
                    <h5 class="mb-2 font-semibold text-black dark:text-white">
                        <i class="fas fa-image text-info mr-2"></i> Logo
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Logo akan ditampilkan di dashboard dan laporan sekolah. Gunakan gambar dengan resolusi minimal 200x200px.</p>
                </div>
                
                <div>
                    <h5 class="mb-2 font-semibold text-black dark:text-white">
                        <i class="fas fa-check-circle text-info mr-2"></i> Status Aktif
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hanya sekolah yang aktif yang dapat digunakan untuk absensi dan fitur lainnya.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Checkbox & Radio styles for Tailadmin */
    input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
    }
    input[type="checkbox"]:checked ~ .box span {
        opacity: 1;
    }
    input[type="radio"]:checked ~ .box {
        border-color: #3C50E0;
    }
    input[type="radio"]:checked ~ .box span {
        background-color: #3C50E0;
    }
    .dark input[type="checkbox"]:checked ~ .box, .dark input[type="radio"]:checked ~ .box {
        border-color: #3C50E0;
    }
    input[type="checkbox"]:disabled ~ .box, input[type="radio"]:disabled ~ .box {
        background-color: #F3F4F6;
        cursor: not-allowed;
    }
    .dark input[type="checkbox"]:disabled ~ .box, .dark input[type="radio"]:disabled ~ .box {
        background-color: #313D4A;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const packagesData = @json($packages);
    const packageSelect = document.querySelector('select[name="package_id"]');
    
    const elements = {
        studentLimit: document.getElementById('input_student_limit'),
        teacherLimit: document.getElementById('input_teacher_limit'),
        botUserLimit: document.getElementById('input_bot_user_limit'),
        historyQuota: document.getElementById('input_history_quota'),
        waEnabled: document.getElementById('wa_enabled'),
        botEnabled: document.getElementById('bot_enabled')
    };

    function applyPackageValues() {
        const pkgId = packageSelect.value;
        
        if (pkgId) {
            // Find package
            const pkg = packagesData.find(p => p.id == pkgId);
            if (pkg) {
                // Set values
                elements.studentLimit.value = pkg.student_limit || '';
                elements.teacherLimit.value = pkg.teacher_limit || '';
                elements.botUserLimit.value = pkg.bot_user_limit || '';
                elements.historyQuota.value = pkg.history_quota_months || '';
                
                elements.waEnabled.checked = pkg.wa_enabled == 1;
                elements.botEnabled.checked = pkg.bot_enabled == 1;
                
                // Disable inputs to show they are managed by package
                elements.studentLimit.readOnly = true;
                elements.teacherLimit.readOnly = true;
                elements.botUserLimit.readOnly = true;
                
                elements.historyQuota.style.pointerEvents = 'none';
                elements.waEnabled.style.pointerEvents = 'none';
                elements.botEnabled.style.pointerEvents = 'none';
                
                elements.studentLimit.classList.add('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
                elements.teacherLimit.classList.add('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
                elements.botUserLimit.classList.add('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
                elements.historyQuota.classList.add('bg-gray-2', 'dark:bg-meta-4');
                document.getElementById('wa_enabled_box').classList.add('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
                document.getElementById('bot_enabled_box').classList.add('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
            }
        } else {
            // Enable inputs
            elements.studentLimit.readOnly = false;
            elements.teacherLimit.readOnly = false;
            elements.botUserLimit.readOnly = false;
            
            elements.historyQuota.style.pointerEvents = 'auto';
            elements.waEnabled.style.pointerEvents = 'auto';
            elements.botEnabled.style.pointerEvents = 'auto';
            
            elements.studentLimit.classList.remove('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
            elements.teacherLimit.classList.remove('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
            elements.botUserLimit.classList.remove('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
            elements.historyQuota.classList.remove('bg-gray-2', 'dark:bg-meta-4');
            document.getElementById('wa_enabled_box').classList.remove('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
            document.getElementById('bot_enabled_box').classList.remove('bg-gray-2', 'dark:bg-meta-4', 'cursor-not-allowed');
        }
    }

    if (packageSelect) {
        packageSelect.addEventListener('change', applyPackageValues);
        // Run once on load if there's old value
        applyPackageValues();
    }
});
</script>
@endpush
@endsection