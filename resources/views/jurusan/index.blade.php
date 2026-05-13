@extends('layouts.app')

@section('title', 'Manajemen Jurusan')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" x-data="{}">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Manajemen Jurusan
        </h2>
        <div class="flex flex-wrap gap-2">
            <button @click="$dispatch('open-modal', 'modalTambahJurusan')"
                class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
                <i class="fas fa-plus"></i> Tambah Jurusan
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <!-- Header & Search -->
        <div class="flex flex-col sm:flex-row justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800 gap-4">
            <h4 class="font-semibold text-gray-800 dark:text-white/90">Daftar Jurusan</h4>

            <form action="{{ route('jurusan.index') }}" method="GET" class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Jurusan..."
                    oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 500);"
                    class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:focus:border-brand-500 text-gray-800 dark:text-white/90">
                <button type="submit"
                    class="absolute right-0 top-0 h-full px-3 text-gray-500 hover:text-brand-500 dark:text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                        <th class="px-4 py-4 xl:pl-6 w-16">No</th>
                        <th class="px-4 py-4">Nama Jurusan</th>
                        <th class="px-4 py-4 text-center">Jumlah Kelas</th>
                        <th class="px-4 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse ($jurusans as $j)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 xl:pl-6">
                                <p class="text-gray-500 dark:text-gray-400">{{ $loop->iteration + $jurusans->firstItem() - 1 }}</p>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                                <p class="font-medium text-gray-800 dark:text-white/90">{{ $j->nama_jurusan }}</p>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                                <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-500">
                                    {{ $j->kelas_count }} Kelas
                                </span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                                <div class="flex items-center justify-center gap-2" x-data="{}">
                                    <!-- Edit -->
                                    <button class="btnEdit text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-2 rounded-lg transition"
                                        data-id="{{ $j->id }}" data-nama="{{ $j->nama_jurusan }}"
                                        @click="$dispatch('open-modal', 'modalEditJurusan')" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Delete -->
                                    <button class="btnHapus text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-lg transition"
                                        data-id="{{ $j->id }}" data-nama="{{ $j->nama_jurusan }}"
                                        @click="$dispatch('open-modal', 'modalHapusJurusan')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border-b border-gray-100 px-4 py-8 dark:border-gray-800 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada data jurusan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $jurusans->links('vendor.pagination.tailwind') }}
        </div>
    </div>

    <!-- ========================= MODALS ========================= -->

    <!-- Modal Tambah -->
    <x-ui.modal id="modalTambahJurusan" :is-open="false" x-data="{}">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Tambah Jurusan</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('jurusan.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Jurusan</label>
                        <input type="text" name="nama_jurusan" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Simpan</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Edit -->
    <x-ui.modal id="modalEditJurusan" :is-open="false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Jurusan</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="#" method="POST" id="formEditJurusan">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Jurusan</label>
                        <input type="text" name="nama_jurusan" id="edit_nama_jurusan" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Update</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Hapus -->
    <x-ui.modal id="modalHapusJurusan" :is-open="false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-error-500">Hapus Jurusan</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="#" method="POST" id="formHapusJurusan">
                @csrf
                @method('DELETE')
                <p class="text-gray-700 dark:text-gray-300 mb-6">Yakin ingin menghapus jurusan: <strong id="hapus_nama_jurusan" class="text-gray-900 dark:text-white"></strong>?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                    <button type="submit" class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600">Hapus</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.btnEdit').on('click', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                $('#edit_nama_jurusan').val(nama);
                $('#formEditJurusan').attr('action', '{{ url('jurusan') }}/' + id);
            });

            // Hapus
            $('.btnHapus').on('click', function () {
                var id = $(this).data('id');
                $('#hapus_nama_jurusan').text($(this).data('nama'));
                $('#formHapusJurusan').attr('action', '{{ url('jurusan') }}/' + id);
            });
        });
    </script>
@endpush
