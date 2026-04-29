@extends('layouts.app')

@section('title', 'Manajemen Device')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" x-data>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-microchip text-brand-500 mr-2"></i> Manajemen Device
        </h2>
        <button @click="$dispatch('open-modal', 'modalTambahDevice')"
            class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
            <i class="fas fa-plus"></i> Tambah Device
        </button>
    </div>

    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
            <h3 class="font-medium text-black dark:text-white">
                Daftar Device
            </h3>
        </div>

        <div class="p-6.5">
            @if(session('success'))
                <div
                    class="mb-6 flex w-full border-l-6 border-success bg-success-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
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
                <div
                    class="mb-6 flex w-full border-l-6 border-error bg-error-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
                    <div class="mr-5 flex h-9 w-full max-w-[36px] items-center justify-center rounded-lg bg-error text-white">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="w-full">
                        <h5 class="mb-1 font-semibold text-[#B45454] dark:text-[#F87171]">Gagal</h5>
                        <p class="text-[#B45454] dark:text-[#F87171]">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-sm">
                            <th class="py-4 px-4 font-medium text-black dark:text-white xl:pl-11" width="5%">No</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Nama Device</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Token (api_key)</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Tipe</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Status</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $d)
                            <tr>
                                <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11">
                                    <p class="text-black dark:text-white">{{ $loop->iteration }}</p>
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    <p class="text-black dark:text-white font-medium">{{ $d->name }}</p>
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    <code
                                        class="rounded bg-gray-100 px-2 py-1 text-sm text-brand-500 dark:bg-gray-800 dark:text-brand-300 font-mono">{{ $d->api_key }}</code>
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    @if($d->type == 'rfid')
                                        <span
                                            class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500"><i
                                                class="fas fa-id-card mr-1 mt-0.5"></i> RFID Only</span>
                                    @elseif($d->type == 'fingerprint')
                                        <span
                                            class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-500"><i
                                                class="fas fa-fingerprint mr-1 mt-0.5"></i> Fingerprint Only</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"><i
                                                class="fas fa-microchip mr-1 mt-0.5"></i> RFID + Finger</span>
                                    @endif
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    @if($d->active)
                                        <span
                                            class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">Active</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                                    @endif
                                </td>
                                <td class="border-b border-gray-100 py-5 px-4 dark:border-gray-800">
                                    <div class="flex items-center space-x-3.5" x-data>
                                        <button
                                            class="text-orange-500 hover:text-orange-700 hover:bg-orange-50 p-2 rounded-lg transition"
                                            @click="$dispatch('open-modal', 'modalEditDevice'); $dispatch('set-edit-device', { id: '{{ $d->id }}', name: '{{ addslashes($d->name) }}', key: '{{ $d->api_key }}', type: '{{ $d->type }}', active: '{{ $d->active }}' })"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition"
                                            @click="$dispatch('open-modal', 'modalHapusDevice'); $dispatch('set-delete-device', { id: '{{ $d->id }}', name: '{{ addslashes($d->name) }}' })"
                                            title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <x-ui.modal id="modalTambahDevice">
        <div class="p-6">
            <h3 class="mb-5 text-2xl font-bold text-gray-800 dark:text-white/90">Tambah Device</h3>
            <form action="{{ route('devices.store') }}" method="POST">
                @csrf
                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Nama Device <span
                            class="text-meta-1">*</span></label>
                    <input type="text" name="name" required
                        class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Token (api_key) <span
                            class="text-meta-1">*</span></label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="api_key" id="create_api_key" required
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <button type="button" onclick="generateToken('create_api_key')"
                            class="rounded bg-gray-200 px-4 py-3 text-sm font-medium text-black hover:bg-gray-300 dark:bg-meta-4 dark:text-white dark:hover:bg-meta-3 transition">Generate</button>
                    </div>
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Tipe Device</label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="type"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
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
                        <select name="active"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-stroke pt-4 dark:border-strokedark">
                    <button type="button" @click="open = false"
                        class="rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">Batal</button>
                    <button type="submit"
                        class="rounded bg-brand-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">Simpan</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Edit -->
    <x-ui.modal id="modalEditDevice">
        <div class="p-6" x-data="{ 
                id: '', name: '', key: '', type: 'rfid_fingerprint', active: '1',
                actionUrl: ''
            }" @set-edit-device.window="
                id = $event.detail.id;
                name = $event.detail.name;
                key = $event.detail.key;
                type = $event.detail.type;
                active = $event.detail.active;
                actionUrl = '{{ url('devices') }}/' + id;
            ">
            <form :action="actionUrl" method="POST">
                @csrf
                @method('PUT')

                <h3 class="mb-5 text-2xl font-bold text-gray-800 dark:text-white/90">Edit Device</h3>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Nama Device <span
                            class="text-meta-1">*</span></label>
                    <input type="text" name="name" x-model="name" required
                        class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Token (api_key) <span
                            class="text-meta-1">*</span></label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="api_key" id="edit_api_key" x-model="key" required
                            class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        <button type="button" @click="key = generateTokenString()"
                            class="rounded bg-gray-200 px-4 py-3 text-sm font-medium text-black hover:bg-gray-300 dark:bg-meta-4 dark:text-white dark:hover:bg-meta-3 transition">Generate</button>
                    </div>
                </div>

                <div class="mb-4.5">
                    <label class="mb-2.5 block text-black dark:text-white">Tipe Device</label>
                    <div class="relative z-20 bg-transparent dark:bg-form-input">
                        <select name="type" x-model="type"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
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
                        <select name="active" x-model="active"
                            class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-stroke pt-4 dark:border-strokedark">
                    <button type="button" @click="open = false"
                        class="rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">Batal</button>
                    <button type="submit"
                        class="rounded bg-brand-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">Update</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Hapus -->
    <x-ui.modal id="modalHapusDevice">
        <div class="p-6" x-data="{ id: '', name: '', actionUrl: '' }" @set-delete-device.window="
                id = $event.detail.id;
                name = $event.detail.name;
                actionUrl = '{{ url('devices') }}/' + id;
            ">
            <form :action="actionUrl" method="POST">
                @csrf
                @method('DELETE')
                <div class="mb-5.5 flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-danger/20 text-danger">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-black dark:text-white">Konfirmasi Hapus</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Yakin ingin menghapus device <strong class="text-black dark:text-white" x-text="name"></strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-stroke pt-4 dark:border-strokedark">
                    <button type="button" @click="open = false"
                        class="rounded border border-stroke px-6 py-2 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">Batal</button>
                    <button type="submit"
                        class="rounded bg-red-500 px-6 py-2 font-medium text-white hover:bg-opacity-90 transition">Ya,
                        Hapus</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

@endsection

@push('scripts')
    <script>
        function generateTokenString() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let out = '';
            for (let i = 0; i < 60; i++) out += chars.charAt(Math.floor(Math.random() * chars.length));
            return out;
        }

        function generateToken(targetId) {
            document.getElementById(targetId).value = generateTokenString();
        }
    </script>
@endpush