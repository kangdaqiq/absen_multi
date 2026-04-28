@extends('layouts.app')

@section('title', 'Edit Sekolah')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Edit Sekolah: {{ $school->name }}
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
                    Form Edit Sekolah
                </h3>
            </div>
            
            <div class="p-6.5">
                <form action="{{ route('super-admin.schools.update', $school) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Nama Sekolah / Instansi <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $school->name) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('name') border-danger @enderror" />
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
                                <option value="school" {{ old('type', $school->type) == 'school' ? 'selected' : '' }}>Sekolah (Siswa, Kelas, Jadwal)</option>
                                <option value="office" {{ old('type', $school->type) == 'office' ? 'selected' : '' }}>Perkantoran (Hanya Karyawan)</option>
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
                        <input type="text" value="{{ $school->code }}" readonly disabled class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-medium text-black outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-meta-4 dark:text-white dark:focus:border-brand-500" />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Kode unik sekolah tidak dapat diubah.</p>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Alamat
                        </label>
                        <textarea name="address" rows="3" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('address') border-danger @enderror">{{ old('address', $school->address) }}</textarea>
                        @error('address')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone', $school->phone) }}" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('phone') border-danger @enderror" />
                            @error('phone')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Email</label>
                            <input type="email" name="email" value="{{ old('email', $school->email) }}" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('email') border-danger @enderror" />
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
                            <input type="text" name="operator_phone" value="{{ old('operator_phone', $school->operator_phone) }}" placeholder="Contoh: 08123456789" class="w-full rounded border border-stroke bg-transparent py-3 pl-12 pr-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('operator_phone') border-danger @enderror" />
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Nomor WhatsApp operator/PIC yang dapat dihubungi untuk koordinasi teknis sekolah ini.</p>
                        @error('operator_phone')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Logo Sekolah</label>
                        @if($school->logo)
                            <div class="mb-3 flex items-center gap-4">
                                <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo" class="h-20 w-20 rounded-lg object-contain border border-stroke dark:border-strokedark p-1 bg-white">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Logo saat ini</p>
                            </div>
                        @endif
                        <input type="file" name="logo" accept="image/*" class="w-full cursor-pointer rounded-lg border-[1.5px] border-stroke bg-transparent font-medium outline-none transition file:mr-5 file:border-collapse file:cursor-pointer file:border-0 file:border-r file:border-solid file:border-stroke file:bg-whiter file:py-3 file:px-5 file:hover:bg-brand-500 file:hover:bg-opacity-10 focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:file:border-form-strokedark dark:file:bg-white/30 dark:file:text-white dark:focus:border-brand-500 @error('logo') border-danger @enderror" />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah logo. Format: JPG, PNG. Maksimal 10MB</p>
                        @error('logo')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Limit Siswa (Quota)</label>
                            <input type="number" name="student_limit" value="{{ old('student_limit', $school->student_limit) }}" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('student_limit') border-danger @enderror" />
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Biarkan kosong atau 0 untuk Unlimited.</p>
                            @error('student_limit')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Limit Guru/Karyawan</label>
                            <input type="number" name="teacher_limit" value="{{ old('teacher_limit', $school->teacher_limit) }}" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('teacher_limit') border-danger @enderror" />
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Biarkan kosong atau 0 untuk Unlimited.</p>
                            @error('teacher_limit')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-5.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            <i class="fas fa-history text-warning mr-1"></i> Kuota Retensi History Absen
                        </label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="history_quota_months" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('history_quota_months') border-danger @enderror">
                                <option value="">-- Tidak Terbatas (Simpan Selamanya) --</option>
                                @foreach([3 => '3 Bulan', 6 => '6 Bulan', 9 => '9 Bulan', 12 => '1 Tahun (12 Bulan)', 24 => '2 Tahun (24 Bulan)', 36 => '3 Tahun (36 Bulan)'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('history_quota_months', $school->history_quota_months) == $val ? 'selected' : '' }}>
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
                                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $school->is_active) ? 'checked' : '' }} class="sr-only" />
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
                                <input type="checkbox" id="wa_enabled" name="wa_enabled" value="1" {{ old('wa_enabled', $school->wa_enabled) ? 'checked' : '' }} class="sr-only" />
                                <div class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                    <span class="opacity-0">
                                        <i class="fas fa-check text-xs text-brand-500"></i>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">Aktifkan Notifikasi WhatsApp</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4.5 border-t border-stroke pt-5 dark:border-strokedark">
                        <a href="{{ route('super-admin.schools.index') }}" class="flex justify-center rounded border border-stroke px-6 py-3 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
                            Batal
                        </a>
                        <button type="submit" class="flex justify-center rounded bg-brand-500 px-6 py-3 font-medium text-white hover:bg-opacity-90 transition">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="flex flex-col gap-9 sm:col-span-1">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">
                    Statistik Sekolah
                </h3>
            </div>
            <div class="p-6.5 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-stroke dark:border-strokedark">
                    <span class="text-gray-500 dark:text-gray-400">Total Siswa</span>
                    <span class="font-semibold text-black dark:text-white text-title-sm">{{ $school->siswa()->count() }}</span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-stroke dark:border-strokedark">
                    <span class="text-gray-500 dark:text-gray-400">Total Guru</span>
                    <span class="font-semibold text-black dark:text-white text-title-sm">{{ $school->guru()->count() }}</span>
                </div>
                <div class="flex justify-between items-center pb-5">
                    <span class="text-gray-500 dark:text-gray-400">Total Admin</span>
                    <span class="font-semibold text-black dark:text-white text-title-sm">{{ $school->users()->where('role', 'admin')->count() }}</span>
                </div>
                <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="flex w-full items-center justify-center gap-2 rounded bg-info px-4 py-2 font-medium text-white hover:bg-opacity-90 transition">
                    <i class="fas fa-users-cog"></i> Kelola Admin
                </a>
            </div>
        </div>

        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">
                    Informasi Sistem
                </h3>
            </div>
            <div class="p-6.5 space-y-4">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 block mb-1">Dibuat:</span>
                    <span class="font-medium text-black dark:text-white">{{ $school->created_at->format('d M Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 block mb-1">Terakhir diupdate:</span>
                    <span class="font-medium text-black dark:text-white">{{ $school->updated_at->format('d M Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Checkbox styles for Tailadmin */
    input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
    }
    input[type="checkbox"]:checked ~ .box span {
        opacity: 1;
    }
    .dark input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
    }
</style>
@endsection