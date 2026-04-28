@extends('layouts.app')

@section('title', 'Manajemen Device: ' . $school->name)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Manajemen Device (ESP32): {{ $school->name }}
    </h2>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <a href="{{ route('super-admin.schools.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md border border-stroke bg-transparent px-6 py-3 text-center font-medium text-black hover:bg-gray-100 dark:border-strokedark dark:text-white dark:hover:bg-boxdark transition">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <button @click="$dispatch('open-add-modal')" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-6 py-3 text-center font-medium text-white hover:bg-opacity-90 transition">
            <i class="fas fa-plus"></i> Tambah Device
        </button>
    </div>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark" x-data="deviceManager()">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Device untuk {{ $school->name }}
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-sm">
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center xl:pl-11" width="5%">No</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Nama Device</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Token (api_key)</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Tipe</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Status</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center" width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $d)
                    <tr>
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 text-center align-middle">
                            <p class="text-black dark:text-white">{{ $loop->iteration }}</p>
                        </td>
                        <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800 align-middle">
                            <p class="text-black dark:text-white font-medium">{{ $d->name }}</p>
                        </td>
                        <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800 align-middle">
                            <code class="rounded bg-gray-100 px-2 py-1 text-sm text-brand-500 dark:bg-gray-800">{{ $d->api_key }}</code>
                        </td>
                        <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800 text-center align-middle">
                            @if($d->type == 'rfid')
                                <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500"><i class="fas fa-id-card mr-1 mt-0.5"></i> RFID Only</span>
                            @elseif($d->type == 'fingerprint')
                                <span class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-500"><i class="fas fa-fingerprint mr-1 mt-0.5"></i> Fingerprint Only</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"><i class="fas fa-microchip mr-1 mt-0.5"></i> RFID + Finger</span>
                            @endif
                        </td>
                        <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800 text-center align-middle">
                            @if($d->active)
                                <span class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                            @endif
                        </td>
                        <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800 text-center align-middle">
                            <div class="flex items-center justify-center space-x-3.5">
                                <button @click="openEditModal({{ $d->id }}, '{{ addslashes($d->name) }}', '{{ $d->api_key }}', '{{ $d->type }}', {{ $d->active }})" class="text-orange-500 hover:text-orange-700 hover:bg-orange-50 p-2 rounded-lg transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="openDeleteModal({{ $d->id }}, '{{ addslashes($d->name) }}')" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center text-gray-500">
                            Belum ada device terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-stroke dark:border-strokedark">
        <p class="text-sm text-gray-500">Note: Token digunakan oleh ESP32 (header <code class="bg-gray-100 px-1 py-0.5 rounded text-black dark:bg-gray-800 dark:text-white">X-API-Key</code>).</p>
    </div>

    <!-- Modal Tambah -->
    <div x-show="isAddModalOpen" @open-add-modal.window="isAddModalOpen = true" style="display: none;" class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 px-4 py-5" @click.self="isAddModalOpen = false">
        <div class="w-full max-w-lg rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark" x-transition>
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
                <h3 class="font-medium text-black dark:text-white">
                    Tambah Device
                </h3>
                <button @click="isAddModalOpen = false" class="text-gray-500 hover:text-black dark:hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="{{ route('super-admin.schools.devices.store', $school) }}" method="POST">
                @csrf
                <div class="p-6.5">
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Nama Device <span class="text-meta-1">*</span></label>
                        <input type="text" name="name" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Token (api_key) <span class="text-meta-1">*</span></label>
                        <div class="flex">
                            <input type="text" name="api_key" id="create_api_key" required class="w-full rounded-l border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                            <button type="button" @click="generateToken('create_api_key')" class="rounded-r border border-l-0 border-stroke bg-gray-2 px-5 py-3 font-medium text-black hover:bg-gray-3 dark:border-strokedark dark:bg-meta-4 dark:text-white dark:hover:bg-meta-4/80 transition">
                                Generate
                            </button>
                        </div>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Tipe Device</label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="type" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                                <option value="rfid_fingerprint">RFID + Fingerprint (Default)</option>
                                <option value="rfid">RFID Only</option>
                                <option value="fingerprint">Fingerprint Only</option>
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-5.5">
                        <label class="mb-2.5 block text-black dark:text-white">Status</label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="active" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4.5 mt-6">
                        <button type="button" @click="isAddModalOpen = false" class="flex justify-center rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
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

    <!-- Modal Edit -->
    <div x-show="isEditModalOpen" style="display: none;" class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 px-4 py-5" @click.self="isEditModalOpen = false">
        <div class="w-full max-w-lg rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark" x-transition>
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
                <h3 class="font-medium text-black dark:text-white">
                    Edit Device
                </h3>
                <button @click="isEditModalOpen = false" class="text-gray-500 hover:text-black dark:hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form :action="'{{ route('super-admin.schools.devices.index', $school) }}/' + editData.id" method="POST">
                @csrf @method('PUT')
                <div class="p-6.5">
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Nama Device <span class="text-meta-1">*</span></label>
                        <input type="text" name="name" x-model="editData.name" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Token (api_key) <span class="text-meta-1">*</span></label>
                        <div class="flex">
                            <input type="text" name="api_key" id="edit_api_key" x-model="editData.api_key" required class="w-full rounded-l border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                            <button type="button" @click="generateToken('edit_api_key')" class="rounded-r border border-l-0 border-stroke bg-gray-2 px-5 py-3 font-medium text-black hover:bg-gray-3 dark:border-strokedark dark:bg-meta-4 dark:text-white dark:hover:bg-meta-4/80 transition">
                                Generate
                            </button>
                        </div>
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">Tipe Device</label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="type" x-model="editData.type" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                                <option value="rfid_fingerprint">RFID + Fingerprint</option>
                                <option value="rfid">RFID Only</option>
                                <option value="fingerprint">Fingerprint Only</option>
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-5.5">
                        <label class="mb-2.5 block text-black dark:text-white">Status</label>
                        <div class="relative z-20 bg-transparent dark:bg-form-input">
                            <select name="active" x-model="editData.active" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4.5 mt-6">
                        <button type="button" @click="isEditModalOpen = false" class="flex justify-center rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
                            Batal
                        </button>
                        <button type="submit" class="flex justify-center rounded bg-brand-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">
                            Update
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div x-show="isDeleteModalOpen" style="display: none;" class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 px-4 py-5" @click.self="isDeleteModalOpen = false">
        <div class="w-full max-w-lg rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark" x-transition>
            <div class="border-b border-stroke bg-danger py-4 px-6.5 text-white flex justify-between items-center rounded-t-sm">
                <h3 class="font-medium text-white">
                    Hapus Device
                </h3>
                <button @click="isDeleteModalOpen = false" class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form :action="'{{ route('super-admin.schools.devices.index', $school) }}/' + deleteData.id" method="POST">
                @csrf @method('DELETE')
                <div class="p-6.5">
                    <p class="text-black dark:text-white">Yakin ingin menghapus device: <strong x-text="deleteData.name"></strong>?</p>

                    <div class="flex justify-end gap-4.5 mt-6 border-t border-stroke pt-5 dark:border-strokedark">
                        <button type="button" @click="isDeleteModalOpen = false" class="flex justify-center rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
                            Batal
                        </button>
                        <button type="submit" class="flex justify-center rounded bg-danger px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">
                            Hapus
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deviceManager() {
    return {
        isAddModalOpen: false,
        isEditModalOpen: false,
        isDeleteModalOpen: false,
        editData: {
            id: '',
            name: '',
            api_key: '',
            type: '',
            active: 1
        },
        deleteData: {
            id: '',
            name: ''
        },
        generateToken(targetId) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let out = '';
            for (let i = 0; i < 60; i++) out += chars.charAt(Math.floor(Math.random() * chars.length));
            
            if (targetId === 'create_api_key') {
                document.getElementById('create_api_key').value = out;
            } else if (targetId === 'edit_api_key') {
                this.editData.api_key = out;
            }
        },
        openEditModal(id, name, key, type, active) {
            this.editData = {
                id: id,
                name: name,
                api_key: key,
                type: type,
                active: active ? 1 : 0
            };
            this.isEditModalOpen = true;
        },
        openDeleteModal(id, name) {
            this.deleteData = {
                id: id,
                name: name
            };
            this.isDeleteModalOpen = true;
        }
    }
}
</script>
@endpush
