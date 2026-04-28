@extends('layouts.app')

@section('title', 'Kelola Lisensi')

@push('styles')
<style>
.key-box {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    letter-spacing: 1px;
    cursor: pointer;
    transition: background .2s;
}
.key-box:hover { opacity: 0.8; }
</style>
@endpush

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-key text-meta-6 mr-2"></i> Kelola Lisensi Self-Hosted
    </h2>
</div>

{{-- Alert Info --}}
<div class="mb-6 flex w-full border-l-6 border-info bg-info/10 px-7 py-3 shadow-md">
    <p class="text-sm text-info">
        <i class="fas fa-info-circle mr-1"></i> Client self-hosted melakukan validasi ke:
        <strong>{{ url('/api/license/validate') }}</strong>
        &nbsp;—&nbsp; Pastikan URL ini terkonfigurasi di <code class="bg-white/50 px-1 py-0.5 rounded">LICENSE_SERVER_URL</code> pada <code class="bg-white/50 px-1 py-0.5 rounded">.env</code> client.
    </p>
</div>

<div class="flex flex-col gap-9 xl:flex-row" x-data="licenseManager()">
    {{-- Form Tambah --}}
    <div class="w-full xl:w-1/3">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white flex items-center gap-2">
                    <i class="fas fa-plus text-brand-500"></i> Tambah Lisensi Baru
                </h3>
            </div>
            
            <div class="p-6.5">
                <form action="{{ route('super-admin.licenses.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Nama Client <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="client_name" value="{{ old('client_name') }}" placeholder="SMA Negeri 1 Jakarta" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('client_name') border-danger @enderror" />
                        @error('client_name')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Max Sekolah</label>
                            <input type="number" name="max_schools" value="{{ old('max_schools', 1) }}" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                            <p class="mt-1.5 text-xs text-gray-500">0 = unlimited</p>
                        </div>
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Max Siswa</label>
                            <input type="number" name="max_students" value="{{ old('max_students', 0) }}" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                            <p class="mt-1.5 text-xs text-gray-500">0 = unlimited</p>
                        </div>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Expired At</label>
                        <input type="date" name="expired_at" value="{{ old('expired_at') }}" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('expired_at') border-danger @enderror" />
                        <p class="mt-1.5 text-xs text-gray-500">Kosongkan = Selamanya</p>
                        @error('expired_at')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            <i class="fas fa-lock text-warning mr-1"></i> Lock Hostname (Opsional)
                        </label>
                        <input type="text" name="allowed_hostname" value="{{ old('allowed_hostname') }}" placeholder="server-client.com" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <p class="mt-1.5 text-xs text-gray-500">Lisensi hanya valid dari hostname ini</p>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Catatan</label>
                        <textarea name="notes" rows="2" placeholder="Nomor HP, info kontrak, dll" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-5.5">
                        <label for="is_active" class="flex cursor-pointer select-none items-center">
                            <div class="relative">
                                <input type="checkbox" id="is_active" name="is_active" checked class="sr-only" />
                                <div class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                    <span class="opacity-0">
                                        <i class="fas fa-check text-xs text-brand-500"></i>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">Aktifkan Lisensi</span>
                        </label>
                    </div>

                    <button type="submit" class="flex w-full justify-center rounded bg-brand-500 p-3 font-medium text-white hover:bg-opacity-90 transition">
                        <i class="fas fa-plus mr-2 mt-1"></i> Buat Lisensi
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Daftar Lisensi --}}
    <div class="w-full xl:w-2/3">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
                <h3 class="font-medium text-black dark:text-white flex items-center gap-2">
                    <i class="fas fa-list text-brand-500"></i> Daftar Lisensi ({{ $licenses->count() }})
                </h3>
            </div>
            
            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-2 text-left dark:bg-meta-4">
                            <th class="py-4 px-4 font-medium text-black dark:text-white xl:pl-6">Client</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">License Key</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Sekolah</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Expired</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Status</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($licenses as $license)
                            <tr>
                                <td class="border-b border-[#eee] py-5 px-4 pl-6 dark:border-strokedark align-top">
                                    <p class="text-black dark:text-white font-medium">{{ $license->client_name }}</p>
                                    @if($license->notes)
                                        <p class="text-xs text-gray-500 mt-1">{{ Str::limit($license->notes, 40) }}</p>
                                    @endif
                                    @if($license->allowed_hostname)
                                        <p class="text-xs text-warning mt-1"><i class="fas fa-lock"></i> {{ $license->allowed_hostname }}</p>
                                    @endif
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                                    <span class="key-box rounded bg-gray-100 px-2 py-1 text-xs text-brand-500 dark:bg-gray-800" onclick="copyKey('{{ $license->license_key }}')" title="Klik untuk copy">
                                        {{ $license->license_key }}
                                    </span>
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                                    <p class="text-black dark:text-white">{{ $license->max_schools === 0 ? '∞' : $license->max_schools }}</p>
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top text-sm">
                                    @if($license->expired_at)
                                        <span class="{{ $license->isExpired() ? 'text-danger font-bold' : 'text-black dark:text-white' }}">
                                            {{ $license->expired_at->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-500">Selamanya</span>
                                    @endif
                                    <div class="mt-2 text-xs text-gray-500">
                                        Last Ping: <br>
                                        @if($license->last_ping_at)
                                            <span title="{{ $license->last_ping_at->format('d M Y H:i:s') }}">{{ $license->last_ping_at->diffForHumans() }}</span>
                                        @else
                                            <span>Belum pernah</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                                    @if($license->status_label == 'Aktif')
                                        <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">Aktif</span>
                                    @elseif($license->status_label == 'Expired')
                                        <span class="inline-flex rounded-full bg-danger/10 px-3 py-1 text-xs font-medium text-danger">Expired</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $license->status_label }}</span>
                                    @endif
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button @click="openEditModal({{ $license->toJson() }})" class="hover:text-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('super-admin.licenses.regenerate', $license) }}" method="POST" class="inline" onsubmit="return confirm('Generate ulang license key untuk {{ $license->client_name }}? Key lama akan tidak berlaku!')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="hover:text-info" title="Regenerate Key">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('super-admin.licenses.destroy', $license) }}" method="POST" class="inline" onsubmit="return confirm('Hapus lisensi {{ $license->client_name }}? Client tidak akan bisa login!')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="hover:text-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="border-b border-[#eee] py-8 px-4 dark:border-strokedark text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-key text-4xl mb-3 text-gray-400"></i>
                                        <span>Belum ada lisensi. Buat lisensi pertama di form sebelah.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="isEditModalOpen" style="display: none;" class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 px-4 py-5" @click.self="isEditModalOpen = false">
        <div class="w-full max-w-lg rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark" x-transition>
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
                <h3 class="font-medium text-black dark:text-white">
                    <i class="fas fa-edit text-brand-500 mr-2"></i> Edit Lisensi
                </h3>
                <button @click="isEditModalOpen = false" class="text-gray-500 hover:text-black dark:hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form :action="'/super-admin/licenses/' + editData.id" method="POST">
                @csrf @method('PUT')
                <div class="p-6.5">
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Nama Client</label>
                        <input type="text" name="client_name" x-model="editData.client_name" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                    </div>

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Max Sekolah</label>
                            <input type="number" name="max_schools" x-model="editData.max_schools" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        </div>
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">Max Siswa</label>
                            <input type="number" name="max_students" x-model="editData.max_students" min="0" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        </div>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Expired At</label>
                        <input type="date" name="expired_at" x-model="editData.expired_at" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <p class="mt-1.5 text-xs text-gray-500">Kosongkan = Selamanya</p>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            <i class="fas fa-lock text-warning mr-1"></i> Lock Hostname
                        </label>
                        <input type="text" name="allowed_hostname" x-model="editData.allowed_hostname" placeholder="Kosongkan = semua hostname" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Catatan</label>
                        <textarea name="notes" x-model="editData.notes" rows="2" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500"></textarea>
                    </div>

                    <div class="mb-5.5">
                        <label for="edit_is_active" class="flex cursor-pointer select-none items-center">
                            <div class="relative">
                                <input type="checkbox" id="edit_is_active" name="is_active" x-model="editData.is_active" class="sr-only" />
                                <div class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                    <span class="opacity-0">
                                        <i class="fas fa-check text-xs text-brand-500"></i>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">Aktifkan Lisensi</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4.5">
                        <button type="button" @click="isEditModalOpen = false" class="flex justify-center rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
                            Batal
                        </button>
                        <button type="submit" class="flex justify-center rounded bg-brand-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">
                            Simpan
                        </button>
                    </div>
                </div>
            </form>
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

@push('scripts')
<script>
function copyKey(key) {
    navigator.clipboard.writeText(key).then(() => {
        alert('License key disalin!');
    }).catch(() => {
        prompt('Salin license key ini:', key);
    });
}

function licenseManager() {
    return {
        isEditModalOpen: false,
        editData: {
            id: '',
            client_name: '',
            max_schools: 0,
            max_students: 0,
            expired_at: '',
            allowed_hostname: '',
            notes: '',
            is_active: true
        },
        openEditModal(license) {
            this.editData = {
                id: license.id,
                client_name: license.client_name,
                max_schools: license.max_schools,
                max_students: license.max_students,
                expired_at: license.expired_at ? license.expired_at.substring(0, 10) : '',
                allowed_hostname: license.allowed_hostname ?? '',
                notes: license.notes ?? '',
                is_active: license.is_active
            };
            this.isEditModalOpen = true;
        }
    }
}
</script>
@endpush
