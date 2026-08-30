@extends('layouts.app')

@section('title', 'Pengajuan Izin & Sakit Siswa')

@section('content')
<div class="space-y-6" x-data="studentLeaveAdmin()">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-title-md2 font-bold text-gray-900 dark:text-white">
                Pengajuan Izin & Sakit Siswa
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Kelola permohonan izin, sakit, dan dispensasi mandiri dari orang tua & siswa.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('portal-izin.index') }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition shadow-theme-xs">
                <i class="fas fa-external-link-alt text-brand-500"></i> Buka Portal Publik
            </a>

            <button type="button" @click="openCreateModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition shadow-theme-xs">
                <i class="fas fa-plus"></i> Input Izin Manual
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-dark shadow-theme-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-warning-50 dark:bg-warning-500/10 text-warning-500 flex items-center justify-center text-xl">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Menunggu</span>
                <strong class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalPending }}</strong>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-dark shadow-theme-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-success-50 dark:bg-success-500/10 text-success-500 flex items-center justify-center text-xl">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Disetujui</span>
                <strong class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalApproved }}</strong>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-dark shadow-theme-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-error-50 dark:bg-error-500/10 text-error-500 flex items-center justify-center text-xl">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Ditolak</span>
                <strong class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalRejected }}</strong>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-dark shadow-theme-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-500/10 text-brand-500 flex items-center justify-center text-xl">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Hari Ini</span>
                <strong class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalToday }}</strong>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden">
        <!-- Tabs & Filters Header -->
        <div class="border-b border-gray-200 dark:border-gray-800 p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                <a href="{{ route('student-leaves.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $statusTab === 'pending' ? 'bg-brand-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                    <span>Menunggu</span>
                    @if($totalPending > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusTab === 'pending' ? 'bg-white/20 text-white' : 'bg-warning-500 text-white' }}">{{ $totalPending }}</span>
                    @endif
                </a>
                <a href="{{ route('student-leaves.index', array_merge(request()->except('status', 'page'), ['status' => 'approved'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $statusTab === 'approved' ? 'bg-brand-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                    Disetujui
                </a>
                <a href="{{ route('student-leaves.index', array_merge(request()->except('status', 'page'), ['status' => 'rejected'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $statusTab === 'rejected' ? 'bg-brand-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                    Ditolak
                </a>
                <a href="{{ route('student-leaves.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $statusTab === 'all' ? 'bg-brand-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200' }}">
                    Semua Data
                </a>
            </div>

            <!-- Filter Controls -->
            <form action="{{ route('student-leaves.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5">
                <input type="hidden" name="status" value="{{ $statusTab }}">

                <select name="kelas_id" onchange="this.form.submit()"
                        class="rounded-xl border border-gray-200 bg-transparent px-3 py-1.5 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>

                <select name="jenis" onchange="this.form.submit()"
                        class="rounded-xl border border-gray-200 bg-transparent px-3 py-1.5 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Jenis</option>
                    <option value="sakit" {{ request('jenis') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="izin" {{ request('jenis') === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="dispensasi" {{ request('jenis') === 'dispensasi' ? 'selected' : '' }}>Dispensasi</option>
                </select>

                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/NISN/kode..."
                           class="rounded-xl border border-gray-200 bg-transparent px-3 py-1.5 pl-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white w-40 sm:w-48">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>

                <button type="submit" class="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs">
                    <i class="fas fa-filter"></i>
                </button>
            </form>
        </div>

        <!-- Table List -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 uppercase font-semibold border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-5 py-3.5">Kode & Tgl</th>
                        <th class="px-5 py-3.5">Siswa & Kelas</th>
                        <th class="px-5 py-3.5">Jenis & Rentang Izin</th>
                        <th class="px-5 py-3.5">Alasan / Bukti</th>
                        <th class="px-5 py-3.5">Pengirim</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                    @forelse($leaves as $leave)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                        <td class="px-5 py-4">
                            <span class="font-mono font-bold text-gray-900 dark:text-white block">{{ $leave->code }}</span>
                            <span class="text-[11px] text-gray-400">{{ $leave->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <strong class="text-gray-900 dark:text-white block text-sm">{{ $leave->student->nama ?? '-' }}</strong>
                            <span class="text-brand-600 dark:text-brand-400 font-medium">{{ $leave->student->kelas->nama_kelas ?? '-' }}</span>
                            <span class="text-gray-400 text-[11px]"> (NISN: {{ $leave->student->nisn ?? '-' }})</span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="font-bold uppercase inline-block px-2 py-0.5 rounded text-[10px] {{ $leave->jenis === 'sakit' ? 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400' : ($leave->jenis === 'dispensasi' ? 'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400') }}">
                                {{ $leave->jenis_label }}
                            </span>
                            <div class="font-semibold text-gray-800 dark:text-gray-200 mt-1">
                                {{ $leave->tanggal_mulai->format('d/m/Y') }}
                                @if(!$leave->tanggal_mulai->isSameDay($leave->tanggal_selesai))
                                    s/d {{ $leave->tanggal_selesai->format('d/m/Y') }}
                                @endif
                                <span class="text-gray-400 font-normal">({{ $leave->durasi_hari }} Hari)</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 max-w-xs">
                            <p class="truncate font-medium text-gray-800 dark:text-gray-200" title="{{ $leave->keterangan }}">{{ $leave->keterangan }}</p>
                            @if($leave->bukti_foto)
                                @php $isPdf = str_ends_with(strtolower($leave->bukti_foto), '.pdf'); @endphp
                                <button type="button" @click="showPreview('{{ asset('storage/' . $leave->bukti_foto) }}', {{ $isPdf ? 'true' : 'false' }})"
                                        class="inline-flex items-center gap-1.5 text-xs text-brand-500 hover:text-brand-600 font-semibold mt-1">
                                    <i class="fas {{ $isPdf ? 'fa-file-pdf text-error-500' : 'fa-image' }}"></i> Lihat Bukti
                                </button>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="font-semibold block">{{ $leave->nama_pengaju ?? '-' }}</span>
                            <span class="text-gray-400 text-[11px] block uppercase">{{ $leave->pengaju }}</span>
                            @if($leave->no_wa_pengaju)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $leave->no_wa_pengaju) }}" target="_blank" class="text-success-600 dark:text-success-400 text-[11px] inline-flex items-center gap-1 hover:underline">
                                    <i class="fab fa-whatsapp"></i> {{ $leave->no_wa_pengaju }}
                                </a>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            {!! $leave->status_badge !!}
                            @if($leave->status === 'rejected' && $leave->rejected_reason)
                                <p class="text-[11px] text-error-500 mt-1 truncate max-w-[150px]" title="{{ $leave->rejected_reason }}">{{ $leave->rejected_reason }}</p>
                            @elseif($leave->status === 'approved' && $leave->approver)
                                <span class="text-[10px] text-gray-400 block mt-0.5">Oleh: {{ $leave->approver->full_name }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            <div class="inline-flex items-center gap-1.5">
                                @if($leave->status === 'pending')
                                    <!-- Approve Button -->
                                    <form action="{{ route('student-leaves.approve', $leave->id) }}" method="POST" onsubmit="return confirm('Setujui pengajuan izin ini dan catat otomatis ke absensi siswa?')">
                                        @csrf
                                        <button type="submit" title="Setujui" class="p-2 rounded-lg bg-success-500 text-white hover:bg-success-600 transition shadow-sm">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <!-- Reject Button -->
                                    <button type="button" @click="openRejectModal({{ $leave->id }}, '{{ $leave->student->nama }}', '{{ $leave->code }}')" title="Tolak"
                                            class="p-2 rounded-lg bg-error-500 text-white hover:bg-error-600 transition shadow-sm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif

                                <!-- Delete Button -->
                                <form action="{{ route('student-leaves.destroy', $leave->id) }}" method="POST" onsubmit="return confirm('Hapus data pengajuan izin ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-500 hover:text-error-500 transition">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3 block opacity-30"></i>
                            Tidak ada data pengajuan izin pada kategori ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leaves->hasPages())
        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            {{ $leaves->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Pratinjau Bukti / Gambar -->
    <div x-show="previewModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto" @click.outside="previewModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800 mb-4">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">Pratinjau Bukti Surat Dokter / Izin</h3>
                <button type="button" @click="previewModal = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="text-center">
                <template x-if="!previewIsPdf">
                    <img :src="previewSrc" alt="Bukti Izin" class="max-h-[70vh] mx-auto rounded-xl shadow-sm object-contain border">
                </template>
                <template x-if="previewIsPdf">
                    <div class="py-8">
                        <i class="fas fa-file-pdf text-5xl text-error-500 mb-3 block"></i>
                        <a :href="previewSrc" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-500 text-white font-bold text-xs hover:bg-brand-600 transition">
                            <i class="fas fa-external-link-alt"></i> Buka Dokumen PDF
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Tolak Pengajuan -->
    <div x-show="rejectModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full p-6 shadow-xl" @click.outside="rejectModal = false">
            <h3 class="font-bold text-base text-gray-900 dark:text-white mb-1">Tolak Pengajuan Izin</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Pengajuan <span class="font-bold font-mono" x-text="rejectCode"></span> an. <strong x-text="rejectName"></strong>
            </p>

            <form :action="'{{ url('/student-leaves') }}/' + rejectId + '/reject'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Alasan Penolakan <span class="text-error-500">*</span>
                    </label>
                    <textarea name="rejected_reason" rows="3" required placeholder="Contoh: Bukti surat dokter tidak jelas / melebihi kuota izin..."
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-transparent px-3 py-2 text-xs outline-none focus:border-error-500 dark:text-white"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="rejectModal = false" class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-error-500 hover:bg-error-600 text-xs font-semibold text-white">
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Input Izin Manual -->
    <div x-show="openCreateModal" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="relative bg-white dark:bg-gray-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto" @click.outside="openCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800 mb-5">
                <div>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white">Input Izin / Sakit Manual</h3>
                    <p class="text-xs text-gray-500">Catat izin siswa langsung dan otomatis setujui ke absensi.</p>
                </div>
                <button type="button" @click="openCreateModal = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form action="{{ route('student-leaves.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Cari Siswa -->
                <div class="mb-4 relative" x-data="{ openDropdown: false, searchQuery: '', searchResults: [], selectedStudent: null, isLoading: false }">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Pilih Siswa <span class="text-error-500">*</span>
                    </label>
                    <input type="text" x-model="searchQuery"
                           @input.debounce.300ms="
                                if (searchQuery.length >= 2) {
                                    isLoading = true;
                                    fetch(`{{ route('portal-izin.search') }}?school_id={{ auth()->user()->school_id }}&q=${encodeURIComponent(searchQuery)}`)
                                        .then(r => r.json())
                                        .then(d => { searchResults = d; openDropdown = d.length > 0; isLoading = false; });
                                } else { searchResults = []; }
                           "
                           placeholder="Ketik nama atau NISN siswa..."
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:text-white">
                    <input type="hidden" name="student_id" :value="selectedStudent ? selectedStudent.id : ''" required>

                    <div x-show="selectedStudent" class="mt-2 p-2 rounded-xl bg-brand-50 dark:bg-brand-500/10 text-xs font-semibold text-brand-600 dark:text-brand-400 flex items-center justify-between">
                        <span x-text="selectedStudent ? `${selectedStudent.nama} (${selectedStudent.kelas})` : ''"></span>
                        <button type="button" @click="selectedStudent = null; searchQuery = ''" class="text-error-500 p-1">Ganti</button>
                    </div>

                    <!-- Dropdown -->
                    <div x-show="openDropdown && searchResults.length > 0" @click.outside="openDropdown = false"
                         class="absolute z-30 left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="item in searchResults" :key="item.id">
                            <button type="button" @click="selectedStudent = item; openDropdown = false; searchQuery = `${item.nama} (${item.kelas})`"
                                    class="w-full text-left p-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700/50 last:border-0 text-xs">
                                <strong x-text="item.nama" class="block"></strong>
                                <span class="text-gray-400 text-[11px]" x-text="`Kelas: ${item.kelas} | NISN: ${item.nisn || '-'}`"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Jenis Izin -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Jenis Izin <span class="text-error-500">*</span></label>
                    <select name="jenis" required class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:text-white dark:bg-gray-900">
                        <option value="sakit">Sakit</option>
                        <option value="izin" selected>Izin</option>
                        <option value="dispensasi">Dispensasi</option>
                    </select>
                </div>

                <!-- Rentang Tanggal -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tgl Mulai <span class="text-error-500">*</span></label>
                        <input type="date" name="tanggal_mulai" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tgl Selesai <span class="text-error-500">*</span></label>
                        <input type="date" name="tanggal_selesai" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:text-white">
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Keterangan <span class="text-error-500">*</span></label>
                    <textarea name="keterangan" rows="3" required placeholder="Masukkan keterangan izin..." class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:text-white"></textarea>
                </div>

                <!-- Upload Bukti -->
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Upload Bukti Foto/Surat (Opsional)</label>
                    <input type="file" name="bukti_foto" accept="image/*,application/pdf" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-800">
                    <button type="button" @click="openCreateModal = false" class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-xs font-bold text-white">
                        Simpan Izin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function studentLeaveAdmin() {
        return {
            previewModal: false,
            previewSrc: '',
            previewIsPdf: false,
            rejectModal: false,
            rejectId: null,
            rejectName: '',
            rejectCode: '',
            openCreateModal: false,

            showPreview(src, isPdf) {
                this.previewSrc = src;
                this.previewIsPdf = isPdf;
                this.previewModal = true;
            },

            openRejectModal(id, name, code) {
                this.rejectId = id;
                this.rejectName = name;
                this.rejectCode = code;
                this.rejectModal = true;
            }
        }
    }
</script>
@endsection
