@extends('layouts.app')

@section('title', 'Manajemen Mata Pelajaran')

@section('content')
    <x-page-header 
        title="Mata Pelajaran" 
        subtitle="Kelola mata pelajaran dan mapping kelas pembelajaran" 
        icon="fas fa-book-open">
        <a href="{{ route('jam-pelajaran.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm text-sm">
            <i class="far fa-clock text-brand-500"></i> Jam Pelajaran
        </a>
        <a href="{{ route('jadwal-pelajaran.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm text-sm">
            <i class="fas fa-calendar-alt text-brand-500"></i> Lihat Jadwal Pelajaran
        </a>
    </x-page-header>

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-500/20 dark:bg-success-500/10 text-success-800 dark:text-success-400">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 p-4 dark:border-error-500/20 dark:bg-error-500/10 text-error-800 dark:text-error-400 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ==================== FORM INPUT MATA PELAJARAN + MAPPING KELAS ==================== -->
    <div class="mb-6 rounded-2xl border border-brand-200 bg-white p-5 sm:p-6 shadow-theme-sm dark:border-brand-500/20 dark:bg-gray-dark">
        <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 font-bold">
                <i class="fas fa-plus"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white/90">Form Input Mata Pelajaran & Mapping Kelas</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Isi nama mata pelajaran dan centang kelas yang mempelajari materi ini</p>
            </div>
        </div>

        <form action="{{ route('mapel.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <!-- Nama Mata Pelajaran -->
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Nama Mata Pelajaran <span class="text-error-500">*</span>
                    </label>

                    <!-- Pilihan Cepat Mata Pelajaran -->
                    <div class="mb-3 flex flex-wrap gap-1.5 p-3 rounded-xl border border-gray-200/80 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-900/40">
                        @php
                            $daftarMapel = [
                                'Pendidikan Agama dan Budi Pekerti',
                                'Pendidikan Pancasila',
                                'Bahasa Indonesia',
                                'Matematika',
                                'Bahasa Inggris',
                                'Ilmu Pengetahuan Alam (IPA)',
                                'Ilmu Pengetahuan Sosial (IPS)',
                                'Ilmu Pengetahuan Alam dan Sosial (IPAS)',
                                'Sejarah',
                                'PJOK (Pendidikan Jasmani, Olahraga, dan Kesehatan)',
                                'Seni dan Budaya',
                                'Informatika',
                                'Prakarya',
                                'Biologi',
                                'Fisika',
                                'Kimia',
                                'Ekonomi',
                                'Sosiologi',
                                'Geografi',
                                'Antropologi',
                                'Matematika Tingkat Lanjut',
                                'Bahasa dan Sastra',
                                'Koding dan Kecerdasan Artifisial',
                                'Projek Kreatif dan Kewirausahaan',
                            ];
                        @endphp
                        @foreach($daftarMapel as $mp)
                            <button type="button" class="btnShortcutMapel inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border border-gray-200 bg-white hover:border-brand-500 hover:text-brand-600 hover:bg-brand-50/60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-brand-400 dark:hover:text-brand-400 dark:hover:bg-brand-500/10 transition shadow-2xs"
                                data-nama="{{ $mp }}">
                                <i class="fas fa-plus text-[9px] text-gray-400"></i> {{ $mp }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Input Nama Mata Pelajaran (di bawah pilihan) -->
                    <div class="relative">
                        <span class="absolute left-3.5 top-3 text-gray-400">
                            <i class="fas fa-book"></i>
                        </span>
                        <input type="text" name="nama_mapel" id="input_nama_mapel" required autofocus
                            placeholder="Contoh: Matematika, Pemrograman Web, Bahasa Inggris, Fisika, dll."
                            value="{{ old('nama_mapel') }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/50 dark:bg-gray-900 py-2.5 pl-10 pr-4 text-sm font-medium outline-none focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:focus:border-brand-500 text-gray-800 dark:text-white transition">
                    </div>
                </div>

                <!-- Mapping Kelas -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50/50 dark:bg-gray-900/40">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 block">
                                <i class="fas fa-chalkboard text-brand-500 mr-1"></i> Mapping Kelas (Target Pembelajaran)
                            </label>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Pilih kelas mana saja yang mendapatkan pelajaran ini. Saat input jadwal, mapel ini hanya muncul di kelas terpilih.
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnSelectAllKelasAdd"
                                class="text-xs text-brand-600 dark:text-brand-400 font-bold hover:underline">
                                Pilih Semua
                            </button>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <button type="button" id="btnDeselectAllKelasAdd"
                                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:underline">
                                Hapus Pilihan
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                        @forelse($kelases as $k)
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 hover:bg-brand-50/50 dark:hover:bg-brand-500/10 cursor-pointer transition select-none">
                                <input type="checkbox" name="kelas_ids[]" value="{{ $k->id }}"
                                    class="kelasCheckboxAdd rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate" title="{{ $k->nama_kelas }}">
                                    {{ $k->nama_kelas }}
                                </span>
                            </label>
                        @empty
                            <p class="text-xs text-gray-400 col-span-full">Belum ada data kelas di sekolah ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-7 py-2.5 text-sm font-bold text-white hover:bg-brand-600 transition shadow-md whitespace-nowrap">
                        <i class="fas fa-save"></i> Simpan Mata Pelajaran & Mapping Kelas
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ==================== TABEL DAFTAR MATA PELAJARAN ==================== -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <!-- Header & Search -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800 gap-4">
            <div class="shrink-0">
                <h4 class="font-bold text-gray-800 dark:text-white/90 text-base">Daftar Mata Pelajaran</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Total: <strong class="text-gray-800 dark:text-white">{{ $mapels->total() }}</strong> mata pelajaran terdaftar
                </p>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="{{ route('mapel.index') }}" method="GET" class="relative w-full sm:w-64">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Mata Pelajaran..."
                        oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 500);"
                        class="w-full rounded-xl border border-gray-200 bg-transparent py-2 pl-3.5 pr-9 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white transition">
                    <button type="submit"
                        class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-brand-500">
                        <i class="fas fa-search text-xs"></i>
                    </button>
                </form>

                @if(request('search'))
                    <a href="{{ route('mapel.index') }}" class="rounded-xl border border-gray-200 p-2 text-xs text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 transition" title="Reset Pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </div>

        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-semibold text-xs">
                        <th class="px-5 py-4 xl:pl-6 w-16">No</th>
                        <th class="px-5 py-4">Nama Mata Pelajaran</th>
                        <th class="px-5 py-4">Kelas</th>
                        <th class="px-5 py-4 text-center">Jadwal Aktif</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($mapels as $m)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-5 py-4 xl:pl-6 text-gray-500 dark:text-gray-400 text-xs">
                                {{ $loop->iteration + $mapels->firstItem() - 1 }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-500 font-bold">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 dark:text-white/90 text-sm">{{ $m->nama_mapel }}</p>
                                        <span class="text-[11px] text-gray-400">ID Mapel: #{{ $m->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    @if($m->kelases->isEmpty())
                                        <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            Semua Kelas (Tidak Dibatasi)
                                        </span>
                                    @else
                                        @foreach($m->kelases as $kc)
                                            <span class="inline-flex rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-100 dark:border-blue-500/20">
                                                {{ $kc->nama_kelas }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($m->jadwals_count > 0)
                                    <a href="{{ route('jadwal-pelajaran.index', ['mapel_id' => $m->id]) }}"
                                        class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-700 hover:bg-success-100 dark:bg-success-500/15 dark:text-success-400 transition"
                                        title="Lihat jadwal mapel ini">
                                        <i class="fas fa-calendar-check mr-1.5"></i> {{ $m->jadwals_count }} Jadwal
                                    </a>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                        0 Jadwal
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5" x-data="{}">
                                    <!-- Edit -->
                                    <button class="btnEdit text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-2 rounded-xl transition"
                                        data-id="{{ $m->id }}"
                                        data-nama="{{ $m->nama_mapel }}"
                                        data-kelas-ids="{{ json_encode($m->kelases->pluck('id')) }}"
                                        @click="$dispatch('open-modal', 'modalEditMapel')" title="Edit Nama & Mapping Kelas">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Delete -->
                                    <button class="btnHapus text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-xl transition"
                                        data-id="{{ $m->id }}" data-nama="{{ $m->nama_mapel }}"
                                        @click="$dispatch('open-modal', 'modalHapusMapel')" title="Hapus Mapel">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 text-xl mb-3">
                                    <i class="fas fa-book"></i>
                                </div>
                                <p class="font-semibold text-gray-700 dark:text-gray-300">Belum ada mata pelajaran</p>
                                <p class="text-xs text-gray-400 mt-1">Gunakan form di atas untuk menambahkan mata pelajaran dan memilih mapping kelas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $mapels->links('vendor.pagination.tailwind') }}
        </div>
    </div>

    <!-- ========================= MODALS ========================= -->

    <!-- Modal Edit Mapel & Mapping Kelas -->
    <x-ui.modal id="modalEditMapel" :is-open="false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white/90">Edit Mata Pelajaran & Mapping Kelas</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="#" method="POST" id="formEditMapel">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" id="edit_nama_mapel" required class="w-full rounded-xl border border-gray-200 bg-transparent px-4 py-2.5 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>

                    <!-- Mapping Kelas Edit -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                Mapping Kelas Target
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btnSelectAllKelasEdit" class="text-xs text-brand-600 dark:text-brand-400 font-bold hover:underline">Pilih Semua</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" id="btnDeselectAllKelasEdit" class="text-xs text-gray-500 hover:underline">Hapus Pilihan</button>
                            </div>
                        </div>

                        <div class="max-h-56 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                            @foreach($kelases as $k)
                                <label class="flex items-center gap-2 p-1.5 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 hover:bg-brand-50/50 cursor-pointer text-xs select-none">
                                    <input type="checkbox" name="kelas_ids[]" value="{{ $k->id }}"
                                        class="kelasCheckboxEdit rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span class="truncate" title="{{ $k->nama_kelas }}">{{ $k->nama_kelas }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Jika tidak ada kelas yang dipilih, mapel dianggap berlaku untuk semua kelas.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-xl border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 text-sm">Batal</button>
                    <button type="submit" class="rounded-xl bg-brand-500 px-5 py-2 text-white hover:bg-brand-600 text-sm font-bold">Update Mapel</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!-- Modal Hapus Mapel -->
    <x-ui.modal id="modalHapusMapel" :is-open="false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-error-500">Hapus Mata Pelajaran</h3>
                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form action="#" method="POST" id="formHapusMapel">
                @csrf
                @method('DELETE')
                <p class="text-gray-700 dark:text-gray-300 mb-6 text-sm">Yakin ingin menghapus mata pelajaran: <strong id="hapus_nama_mapel" class="text-gray-900 dark:text-white font-bold"></strong>?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-xl border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 text-sm">Batal</button>
                    <button type="submit" class="rounded-xl bg-error-500 px-5 py-2 text-white hover:bg-error-600 text-sm font-bold">Hapus</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Select All / Deselect All for Add Form
            $('#btnSelectAllKelasAdd').on('click', function () {
                $('.kelasCheckboxAdd').prop('checked', true);
            });
            $('#btnDeselectAllKelasAdd').on('click', function () {
                $('.kelasCheckboxAdd').prop('checked', false);
            });

            // Select All / Deselect All for Edit Form
            $('#btnSelectAllKelasEdit').on('click', function () {
                $('.kelasCheckboxEdit').prop('checked', true);
            });
            $('#btnDeselectAllKelasEdit').on('click', function () {
                $('.kelasCheckboxEdit').prop('checked', false);
            });

            // Edit button handler
            $('.btnEdit').on('click', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var kelasIds = $(this).data('kelas-ids') || [];

                $('#edit_nama_mapel').val(nama);
                $('#formEditMapel').attr('action', '{{ url('mapel') }}/' + id);

                // Reset and check mapped classes
                $('.kelasCheckboxEdit').prop('checked', false);
                if (Array.isArray(kelasIds)) {
                    kelasIds.forEach(function (kid) {
                        $('.kelasCheckboxEdit[value="' + kid + '"]').prop('checked', true);
                    });
                }
            });

            // Hapus button handler
            $('.btnHapus').on('click', function () {
                var id = $(this).data('id');
                $('#hapus_nama_mapel').text($(this).data('nama'));
                $('#formHapusMapel').attr('action', '{{ url('mapel') }}/' + id);
            });

            // Shortcut Pelajaran Kurikulum Merdeka
            $('.btnShortcutMapel').on('click', function () {
                var nama = $(this).data('nama');
                $('#input_nama_mapel').val(nama).focus();

                var btn = $(this);
                btn.addClass('ring-2 ring-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/20');
                setTimeout(function () {
                    btn.removeClass('ring-2 ring-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/20');
                }, 400);
            });
        });
    </script>
@endpush
