<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pengajuan Izin & Sakit Siswa - {{ $school->name ?? config('app.name', 'Sistem Absensi') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-indigo-50/40 to-slate-100 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 min-h-full text-slate-800 dark:text-slate-100 flex flex-col justify-between p-4 sm:p-6 lg:p-8">

    <div class="max-w-2xl mx-auto w-full my-auto">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-500/10 dark:bg-brand-500/20 text-brand-500 shadow-sm mb-4 border border-brand-500/20">
                <i class="fas fa-file-medical text-3xl"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                Pengajuan Izin & Sakit Online
            </h1>
            <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
                {{ $school->name ?? 'Portal Mandiri Orang Tua & Siswa' }}
            </p>
        </div>

        @if(session('error'))
        <div class="mb-6 rounded-xl bg-error-50 dark:bg-error-500/10 p-4 border border-error-200 dark:border-error-800/40 text-error-700 dark:text-error-400 text-sm flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <div>{{ session('error') }}</div>
        </div>
        @endif

        <!-- Card Form -->
        <div class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl rounded-3xl border border-slate-200/80 dark:border-gray-800 shadow-xl shadow-slate-200/50 dark:shadow-none p-6 sm:p-8"
             x-data="leavePortal()">

            <form action="{{ route('portal-izin.store') }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit">
                @csrf

                <!-- 1. Pemilihan Sekolah jika Multi-Tenant tanpa School Context -->
                @if(!$school && isset($schools) && $schools->count() > 1)
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Pilih Sekolah <span class="text-error-500">*</span>
                    </label>
                    <select name="school_id" x-model="selectedSchoolId" @change="resetStudent" required
                            class="w-full rounded-xl border border-slate-200 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition">
                        <option value="">-- Pilih Sekolah --</option>
                        @foreach($schools as $sc)
                            <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <input type="hidden" name="school_id" value="{{ $school->id ?? ($schools->first()->id ?? '') }}" x-model="selectedSchoolId">
                @endif

                <!-- 2. Pencarian Siswa (Autocomplete) -->
                <div class="mb-5 relative" x-data="{ openDropdown: false }">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Nama / NISN Siswa <span class="text-error-500">*</span>
                    </label>

                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.300ms="searchStudents()"
                               @focus="if(searchResults.length > 0) openDropdown = true"
                               placeholder="Ketik minimal 2 huruf Nama atau NISN siswa..."
                               :disabled="!selectedSchoolId"
                               class="w-full rounded-xl border border-slate-200 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/50 px-4 py-3 pl-11 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fas fa-search" x-show="!isLoading"></i>
                            <i class="fas fa-spinner fa-spin text-brand-500" x-show="isLoading"></i>
                        </div>
                    </div>

                    <input type="hidden" name="student_id" :value="selectedStudent ? selectedStudent.id : ''" required>

                    <!-- Selected Student Card -->
                    <div x-show="selectedStudent" class="mt-3 p-3.5 rounded-xl bg-brand-50/70 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-brand-500 text-white flex items-center justify-center font-bold text-sm">
                                <span x-text="selectedStudent ? selectedStudent.nama.substring(0,2).toUpperCase() : ''"></span>
                            </div>
                            <div>
                                <div class="font-bold text-sm text-slate-800 dark:text-white" x-text="selectedStudent?.nama"></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    Kelas: <span class="font-semibold text-brand-600 dark:text-brand-400" x-text="selectedStudent?.kelas"></span> |
                                    NISN: <span x-text="selectedStudent?.nisn || '-'"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="resetStudent" class="text-xs font-semibold text-slate-400 hover:text-error-500 p-1.5 transition">
                            <i class="fas fa-times"></i> Ganti
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div x-show="openDropdown && searchResults.length > 0"
                         @click.outside="openDropdown = false"
                         class="absolute z-30 left-0 right-0 mt-1.5 bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-xl overflow-hidden max-h-60 overflow-y-auto">
                        <template x-for="item in searchResults" :key="item.id">
                            <button type="button"
                                    @click="selectStudent(item); openDropdown = false"
                                    class="w-full text-left p-3 hover:bg-slate-50 dark:hover:bg-gray-700/60 border-b border-slate-100 dark:border-gray-700/40 last:border-0 flex items-center justify-between transition">
                                <div>
                                    <div class="font-semibold text-sm text-slate-800 dark:text-white" x-text="item.nama"></div>
                                    <div class="text-xs text-slate-400">
                                        NISN: <span x-text="item.nisn || '-'"></span> • Kelas: <span class="text-brand-500 font-medium" x-text="item.kelas"></span>
                                    </div>
                                </div>
                                <span class="text-xs bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 font-medium px-2 py-1 rounded-md">Pilih</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 3. Jenis Izin -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Jenis Pengajuan <span class="text-error-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="jenis" value="sakit" x-model="jenis" class="peer sr-only">
                            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-gray-800 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50/50 dark:peer-checked:bg-brand-500/10 peer-checked:text-brand-600 dark:peer-checked:text-brand-400 hover:bg-slate-50 dark:hover:bg-gray-800/60 transition">
                                <i class="fas fa-head-side-cough text-xl mb-1 block"></i>
                                <span class="text-xs font-bold">Sakit</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="jenis" value="izin" x-model="jenis" class="peer sr-only">
                            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-gray-800 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50/50 dark:peer-checked:bg-brand-500/10 peer-checked:text-brand-600 dark:peer-checked:text-brand-400 hover:bg-slate-50 dark:hover:bg-gray-800/60 transition">
                                <i class="fas fa-calendar-day text-xl mb-1 block"></i>
                                <span class="text-xs font-bold">Izin</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="jenis" value="dispensasi" x-model="jenis" class="peer sr-only">
                            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-gray-800 text-center peer-checked:border-brand-500 peer-checked:bg-brand-50/50 dark:peer-checked:bg-brand-500/10 peer-checked:text-brand-600 dark:peer-checked:text-brand-400 hover:bg-slate-50 dark:hover:bg-gray-800/60 transition">
                                <i class="fas fa-medal text-xl mb-1 block"></i>
                                <span class="text-xs font-bold">Dispensasi</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 4. Rentang Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Tanggal Mulai <span class="text-error-500">*</span>
                        </label>
                        <input type="date" name="tanggal_mulai" x-model="tanggalMulai" @change="if(!tanggalSelesai || tanggalSelesai < tanggalMulai) tanggalSelesai = tanggalMulai" required
                               class="w-full rounded-xl border border-slate-200 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Tanggal Selesai <span class="text-error-500">*</span>
                        </label>
                        <input type="date" name="tanggal_selesai" x-model="tanggalSelesai" :min="tanggalMulai" required
                               class="w-full rounded-xl border border-slate-200 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition">
                    </div>
                </div>

                <!-- 5. Alasan / Keterangan -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Alasan / Keterangan Lengkap <span class="text-error-500">*</span>
                    </label>
                    <textarea name="keterangan" rows="3" required placeholder="Contoh: Demam tinggi dan batuk, istirahat atas petunjuk dokter..."
                              class="w-full rounded-xl border border-slate-200 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-800/50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition"></textarea>
                </div>

                <!-- 6. Upload Bukti Foto / Surat Dokter -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Upload Surat Dokter / Bukti Foto <span class="text-xs font-normal text-slate-400">(Opsional, Maks 5MB)</span>
                    </label>
                    <div class="border-2 border-dashed border-slate-200 dark:border-gray-800 rounded-2xl p-4 text-center hover:border-brand-500 transition relative bg-slate-50/30 dark:bg-gray-800/20">
                        <input type="file" name="bukti_foto" id="bukti-file" accept="image/jpeg,image/png,image/jpg,application/pdf"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                               @change="previewFile">
                        <div class="flex flex-col items-center justify-center py-2" x-show="!previewUrl">
                            <i class="fas fa-cloud-arrow-up text-2xl text-slate-400 mb-2"></i>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                Klik atau tarik foto surat dokter ke sini
                            </span>
                            <span class="text-[11px] text-slate-400 mt-0.5">JPG, PNG, atau PDF (Maks. 5MB)</span>
                        </div>
                        <div x-show="previewUrl" class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-slate-200 dark:border-gray-700">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <img :src="previewUrl" class="w-12 h-12 object-cover rounded-lg border" x-show="isImage">
                                <i class="fas fa-file-pdf text-3xl text-error-500 p-2" x-show="!isImage"></i>
                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate" x-text="fileName"></span>
                            </div>
                            <span class="text-xs font-semibold text-brand-500 mr-2">Siap diunggah</span>
                        </div>
                    </div>
                </div>

                <!-- 7. Data Pengaju (Orang Tua / Siswa) -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-800/40 border border-slate-200/80 dark:border-gray-800 mb-6 space-y-4">
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Informasi Pengirim</div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Pengirim</label>
                            <select name="pengaju" x-model="pengaju" required
                                    class="w-full rounded-lg border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-xs outline-none focus:border-brand-500">
                                <option value="ortu">Orang Tua / Wali</option>
                                <option value="siswa">Siswa Sendiri</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Nama Pengirim</label>
                            <input type="text" name="nama_pengaju" x-model="namaPengaju" required placeholder="Nama Anda"
                                   class="w-full rounded-lg border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-xs outline-none focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">No. WhatsApp</label>
                            <input type="tel" name="no_wa_pengaju" x-model="noWaPengaju" required placeholder="08xxxxxxxxxx"
                                   class="w-full rounded-lg border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-xs outline-none focus:border-brand-500">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        :disabled="!selectedStudent || isSubmitting"
                        class="w-full py-4 px-6 rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold text-base shadow-lg shadow-brand-500/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none flex items-center justify-center gap-2 transition active:scale-[0.99]">
                    <i class="fas fa-paper-plane" x-show="!isSubmitting"></i>
                    <i class="fas fa-spinner fa-spin" x-show="isSubmitting"></i>
                    <span x-text="isSubmitting ? 'Mengirim Pengajuan...' : 'Kirim Pengajuan Izin'"></span>
                </button>
            </form>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-6 text-xs text-slate-400">
            Sistem Absensi & Perizinan Sekolah &copy; {{ date('Y') }}
        </div>
    </div>

    <script>
        function leavePortal() {
            return {
                selectedSchoolId: '{{ $school->id ?? ($schools->first()->id ?? '') }}',
                searchQuery: '',
                searchResults: [],
                selectedStudent: null,
                isLoading: false,
                isSubmitting: false,
                jenis: 'sakit',
                tanggalMulai: '{{ date('Y-m-d') }}',
                tanggalSelesai: '{{ date('Y-m-d') }}',
                pengaju: 'ortu',
                namaPengaju: '',
                noWaPengaju: '',
                previewUrl: null,
                fileName: '',
                isImage: true,

                searchStudents() {
                    if (!this.selectedSchoolId || this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    this.isLoading = true;
                    fetch(`{{ route('portal-izin.search') }}?school_id=${this.selectedSchoolId}&q=${encodeURIComponent(this.searchQuery)}`)
                        .then(res => res.json())
                        .then(data => {
                            this.searchResults = data;
                            this.openDropdown = data.length > 0;
                            this.isLoading = false;
                        })
                        .catch(() => {
                            this.isLoading = false;
                        });
                },

                selectStudent(student) {
                    this.selectedStudent = student;
                    this.searchQuery = `${student.nama} (${student.kelas})`;
                    if (this.pengaju === 'ortu' && student.nama_ortu) {
                        this.namaPengaju = student.nama_ortu;
                    }
                    if (this.pengaju === 'ortu' && student.wa_ortu) {
                        this.noWaPengaju = student.wa_ortu;
                    } else if (this.pengaju === 'siswa' && student.no_wa) {
                        this.noWaPengaju = student.no_wa;
                    }
                },

                resetStudent() {
                    this.selectedStudent = null;
                    this.searchQuery = '';
                    this.searchResults = [];
                },

                previewFile(e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    this.fileName = file.name;
                    this.isImage = file.type.startsWith('image/');
                    if (this.isImage) {
                        const reader = new FileReader();
                        reader.onload = (re) => { this.previewUrl = re.target.result; };
                        reader.readAsDataURL(file);
                    } else {
                        this.previewUrl = 'pdf';
                    }
                },

                handleSubmit() {
                    this.isSubmitting = true;
                }
            }
        }
    </script>
</body>
</html>
