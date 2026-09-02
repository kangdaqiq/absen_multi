@extends('layouts.app')

@section('title', 'Template Pesan Notifikasi')

@section('content')
<div x-data="templateManager()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2.5">
                <i class="fas fa-comments text-brand-500"></i> Template Pesan Notifikasi
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Sesuaikan teks pesan WhatsApp & Telegram untuk kehadiran siswa dan guru sesuai kebutuhan sekolah.
            </p>
        </div>

        @if(auth()->user()->isSuperAdmin() && $schools->count() > 1)
        <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Pilih Sekolah:</label>
            <select @change="window.location.href = '{{ route('settings.templates.index') }}?school_id=' + $event.target.value"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-white outline-none focus:border-brand-500">
                @foreach($schools as $sch)
                    <option value="{{ $sch->id }}" {{ $schoolId == $sch->id ? 'selected' : '' }}>{{ $sch->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="rounded-xl bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/15 dark:text-success-400 flex items-center gap-2.5 shadow-sm">
            <i class="fas fa-check-circle text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-400 flex items-center gap-2.5 shadow-sm">
            <i class="fas fa-exclamation-circle text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Container with Tabs -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden">
        <!-- Horizontal Scrollable Tabs Header -->
        <div class="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/40 px-4 sm:px-6">
            <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto py-3 no-scrollbar" aria-label="Tabs">
                @php
                    $tabGroups = [
                        'masuk'     => ['label' => 'Absen Masuk', 'icon' => 'fa-sign-in-alt', 'cats' => ['checkin_siswa', 'checkin_ortu']],
                        'terlambat' => ['label' => 'Terlambat', 'icon' => 'fa-clock', 'cats' => ['late_siswa', 'late_ortu']],
                        'pulang'    => ['label' => 'Absen Pulang', 'icon' => 'fa-sign-out-alt', 'cats' => ['checkout_siswa', 'checkout_ortu']],
                        'cepat'     => ['label' => 'Pulang Cepat', 'icon' => 'fa-hourglass-half', 'cats' => ['early_checkout_siswa', 'early_checkout_ortu']],
                        'izin'      => ['label' => 'Izin & Sakit', 'icon' => 'fa-file-medical', 'cats' => ['izin_siswa', 'izin_ortu', 'sakit_siswa', 'sakit_ortu']],
                        'alpha'     => ['label' => 'Alpha & Bolos', 'icon' => 'fa-user-times', 'cats' => ['alpha_siswa', 'alpha_ortu', 'bolos_siswa', 'bolos_ortu']],
                        'guru'      => ['label' => 'Guru & Pegawai', 'icon' => 'fa-chalkboard-teacher', 'cats' => ['checkin_guru', 'late_guru', 'checkout_guru', 'early_checkout_guru']],
                    ];
                @endphp

                @foreach($tabGroups as $tabKey => $tabData)
                    @php
                        $tabCustomCount = 0;
                        foreach($tabData['cats'] as $c) {
                            $tabCustomCount += ($templates[$c] ?? collect())->count();
                        }
                    @endphp
                    <button type="button" @click="activeTab = '{{ $tabKey }}'"
                        :class="activeTab === '{{ $tabKey }}' ? 'bg-white text-brand-600 shadow-sm border-gray-200 dark:bg-gray-800 dark:text-brand-400 dark:border-gray-700' : 'text-gray-600 hover:text-gray-900 hover:bg-white/60 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800/50 border-transparent'"
                        class="flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs sm:text-sm font-semibold whitespace-nowrap transition-all">
                        <i class="fas {{ $tabData['icon'] }} text-xs"></i>
                        <span>{{ $tabData['label'] }}</span>
                        @if($tabCustomCount > 0)
                            <span class="rounded-full bg-brand-500 px-1.5 py-0.2 text-[10px] font-bold text-white leading-none">
                                {{ $tabCustomCount }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-6 space-y-8">
            @foreach($tabGroups as $tabKey => $tabData)
                <div x-show="activeTab === '{{ $tabKey }}'" x-cloak class="space-y-8">
                    @foreach($tabData['cats'] as $catKey)
                        @php
                            $catInfo = $categories[$catKey] ?? null;
                            if (!$catInfo) continue;
                            $catTemplates = $templates[$catKey] ?? collect();
                        @endphp
                        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900/60 p-5 sm:p-6 space-y-5">
                            <!-- Category Header -->
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold
                                            {{ $catInfo['target'] === 'ortu' ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400' : ($catInfo['target'] === 'guru' ? 'bg-purple-100 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400') }}">
                                            @if($catInfo['target'] === 'ortu')
                                                <i class="fas fa-user-friends"></i>
                                            @elseif($catInfo['target'] === 'guru')
                                                <i class="fas fa-user-tie"></i>
                                            @else
                                                <i class="fas fa-user-graduate"></i>
                                            @endif
                                        </span>
                                        <h3 class="text-base font-bold text-gray-800 dark:text-white/90">{{ $catInfo['label'] }}</h3>
                                        <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold
                                            {{ $catInfo['target'] === 'ortu' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' : ($catInfo['target'] === 'guru' ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400') }}">
                                            Penerima: {{ strtoupper($catInfo['target']) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 ml-9.5">{{ $catInfo['desc'] }}</p>
                                </div>

                                <div class="flex items-center gap-2 self-start sm:self-auto ml-9.5 sm:ml-0">
                                    @if($catTemplates->count() > 0)
                                        <form action="{{ route('settings.templates.resetCategory') }}" method="POST" onsubmit="return confirm('Kembalikan kategori ini ke template bawaan sistem? Semua variasi kustom kategori ini akan dihapus.')">
                                            @csrf
                                            <input type="hidden" name="category" value="{{ $catKey }}">
                                            @if(auth()->user()->isSuperAdmin())
                                                <input type="hidden" name="school_id" value="{{ $schoolId }}">
                                            @endif
                                            <button type="submit" class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                                                <i class="fas fa-undo mr-1"></i> Reset Default
                                            </button>
                                        </form>
                                    @endif
                                    <button type="button" @click="openCreateModal('{{ $catKey }}', '{{ $catInfo['label'] }}')"
                                        class="rounded-xl bg-brand-500 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition flex items-center gap-1.5 shadow-sm">
                                        <i class="fas fa-plus"></i> Tambah Variasi
                                    </button>
                                </div>
                            </div>

                            <!-- Variations List or Empty State -->
                            @if($catTemplates->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($catTemplates as $idx => $tmpl)
                                        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-4 space-y-3 shadow-2xs hover:border-brand-300 dark:hover:border-brand-500/40 transition">
                                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                                        {{ $idx + 1 }}
                                                    </span>
                                                    <span class="font-semibold text-xs text-gray-800 dark:text-white/90">
                                                        {{ $tmpl->title ?: 'Variasi ' . ($idx + 1) }}
                                                    </span>
                                                    @if($tmpl->is_active)
                                                        <span class="rounded-full bg-success-50 px-2 py-0.2 text-[10px] font-semibold text-success-600 dark:bg-success-500/10 dark:text-success-400">Aktif</span>
                                                    @else
                                                        <span class="rounded-full bg-gray-100 px-2 py-0.2 text-[10px] font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Non-Aktif</span>
                                                    @endif
                                                </div>

                                                <div class="flex items-center gap-1">
                                                    <!-- Toggle Switch -->
                                                    <form action="{{ route('settings.templates.toggle', $tmpl->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" title="{{ $tmpl->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                            class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                                            <i class="fas {{ $tmpl->is_active ? 'fa-toggle-on text-brand-500 text-sm' : 'fa-toggle-off text-gray-400 text-sm' }}"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Edit Button -->
                                                    <button type="button" @click="openEditModal({{ json_encode($tmpl) }}, '{{ $catInfo['label'] }}')"
                                                        class="rounded-lg p-1 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-500/10 transition" title="Edit Variasi">
                                                        <i class="fas fa-edit text-xs"></i>
                                                    </button>

                                                    <!-- Delete Button -->
                                                    <form action="{{ route('settings.templates.destroy', $tmpl->id) }}" method="POST" onsubmit="return confirm('Hapus variasi template ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-lg p-1 text-error-600 hover:bg-error-50 dark:text-error-400 dark:hover:bg-error-500/10 transition" title="Hapus">
                                                            <i class="fas fa-trash text-xs"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="rounded-lg bg-gray-50 dark:bg-gray-950/60 p-3 font-mono text-[11px] text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed border border-gray-100 dark:border-gray-800/80 max-h-44 overflow-y-auto">{{ $tmpl->content }}</div>

                                            <div class="flex justify-end pt-1">
                                                <button type="button" @click="previewMessage({{ json_encode($tmpl->content) }}, '{{ $catKey }}')"
                                                    class="text-[11px] text-brand-500 hover:text-brand-600 hover:underline flex items-center gap-1 font-medium">
                                                    <i class="fas fa-eye"></i> Pratinjau Chat
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-800 p-4 bg-gray-50/50 dark:bg-gray-900/40 flex flex-col sm:flex-row items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400">
                                            <i class="fas fa-magic text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-xs font-semibold text-gray-800 dark:text-white/90">Menggunakan Template Bawaan Sistem (Default)</div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400">Pesan otomatis menggunakan 3 variasi bawaan sistem dengan salam & penutup ramah.</div>
                                        </div>
                                    </div>
                                    <button type="button" @click="openCreateModal('{{ $catKey }}', '{{ $catInfo['label'] }}')"
                                        class="rounded-lg bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-500/20 px-3 py-1.5 text-xs font-semibold hover:bg-brand-500 hover:text-white transition whitespace-nowrap">
                                        <i class="fas fa-plus mr-1"></i> Buat Kustom
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================= MODAL TAMBAH / EDIT TEMPLATE ========================= -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-99999 flex min-h-full items-center justify-center overflow-y-auto p-4 sm:p-6 bg-gray-900/50 backdrop-blur-sm">
        <div @click.away="showModal = false" class="relative w-full max-w-3xl max-h-[90vh] my-auto flex flex-col rounded-3xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white/90" x-text="isEdit ? 'Edit Variasi Template' : 'Tambah Variasi Template'"></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="categoryLabel"></p>
                </div>
                <button type="button" @click="showModal = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="overflow-y-auto p-6 space-y-5">
                <form id="templateForm" :action="isEdit ? ('{{ url('settings/templates') }}/' + editId) : '{{ route('settings.templates.store') }}'" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="category" :value="formCategory">
                    @if(auth()->user()->isSuperAdmin())
                        <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    @endif

                    <div class="space-y-4">
                        <!-- Judul Variasi -->
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Nama Variasi
                            </label>
                            <input type="text" name="title" x-model="formTitle" placeholder="Contoh: Variasi 1 - Ramah & Santai"
                                class="w-full rounded-xl border border-gray-200 bg-transparent px-4 py-2.5 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        </div>

                        <!-- Variable Inserter Toolbar -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                    <i class="fas fa-tags text-brand-500"></i> Sisipkan Variabel Dinamis (Klik untuk Menambahkan):
                                </label>
                            </div>
                            <div class="flex flex-wrap gap-1.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 p-3 border border-gray-200 dark:border-gray-800">
                                @foreach($placeholders as $tag => $label)
                                    <button type="button" @click="insertTag('{{ $tag }}')"
                                        title="{{ $label }}"
                                        class="rounded-lg bg-white dark:bg-gray-900 px-2.5 py-1 text-xs font-mono font-medium text-brand-600 dark:text-brand-400 border border-gray-200 dark:border-gray-700 hover:bg-brand-500 hover:text-white dark:hover:bg-brand-500 dark:hover:text-white transition shadow-2xs">
                                        {{ $tag }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Content Editor & Live Preview Tabs / Split -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Textarea Editor -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Format Teks Pesan <span class="text-error-500">*</span>
                                </label>
                                <textarea id="templateContentTextarea" name="content" x-model="formContent" rows="12" required
                                    @input="updateLivePreview()"
                                    placeholder="Tulis format pesan disini..."
                                    class="w-full rounded-xl border border-gray-200 bg-transparent p-3.5 text-xs font-mono outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white leading-relaxed"></textarea>
                                <p class="mt-1 text-[11px] text-gray-400">
                                    Gunakan <code>*tebal*</code> untuk teks tebal dan <code>_miring_</code> untuk teks miring.
                                </p>
                            </div>

                            <!-- Live Chat Bubble Preview -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 flex items-center justify-between">
                                    <span><i class="fab fa-whatsapp text-success-500 mr-1"></i> Live Chat Preview</span>
                                    <span class="text-[10px] font-normal text-gray-400">Data Simulasi</span>
                                </label>
                                <div class="rounded-xl border border-gray-200 bg-[#EFEAE2] dark:bg-gray-950 p-4 h-[255px] overflow-y-auto shadow-inner flex flex-col justify-start">
                                    <div class="self-start max-w-[95%] rounded-2xl rounded-tl-xs bg-white dark:bg-gray-800 p-3.5 text-xs text-gray-800 dark:text-gray-200 shadow-sm whitespace-pre-wrap leading-relaxed border border-black/5 dark:border-white/5"
                                        x-text="livePreviewText || 'Tulis pesan untuk melihat pratinjau...'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button type="button" @click="showModal = false"
                            class="rounded-xl border border-gray-200 px-5 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-brand-500 px-6 py-2.5 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-sm">
                            <i class="fas fa-save mr-1"></i> Simpan Variasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================= MODAL SIMULASI PREVIEW CHAT ========================= -->
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-99999 flex min-h-full items-center justify-center overflow-y-auto p-4 sm:p-6 bg-gray-900/50 backdrop-blur-sm">
        <div @click.away="showPreviewModal = false" class="relative w-full max-w-md max-h-[90vh] my-auto flex flex-col rounded-3xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    <i class="fab fa-whatsapp text-success-500 text-lg"></i> Simulasi Pesan
                </h3>
                <button type="button" @click="showPreviewModal = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-5 bg-[#EFEAE2] dark:bg-gray-950 overflow-y-auto max-h-[60vh]">
                <div class="self-start rounded-2xl rounded-tl-xs bg-white dark:bg-gray-800 p-4 text-xs text-gray-800 dark:text-gray-200 shadow-md whitespace-pre-wrap leading-relaxed border border-black/5 dark:border-white/5"
                    x-text="previewModalText"></div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-900 flex justify-end border-t border-gray-100 dark:border-gray-800">
                <button type="button" @click="showPreviewModal = false" class="rounded-lg bg-gray-100 dark:bg-gray-800 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function templateManager() {
    return {
        activeTab: 'masuk',
        showModal: false,
        isEdit: false,
        editId: null,
        formCategory: 'checkin_siswa',
        categoryLabel: '',
        formTitle: '',
        formContent: '',
        livePreviewText: '',
        showPreviewModal: false,
        previewModalText: '',

        openCreateModal(catKey, catLabel) {
            this.isEdit = false;
            this.editId = null;
            this.formCategory = catKey;
            this.categoryLabel = catLabel;
            this.formTitle = '';
            
            // Default sample content based on category
            this.formContent = this.getDefaultSample(catKey);
            this.updateLivePreview();
            this.showModal = true;
        },

        openEditModal(tmpl, catLabel) {
            this.isEdit = true;
            this.editId = tmpl.id;
            this.formCategory = tmpl.category;
            this.categoryLabel = catLabel;
            this.formTitle = tmpl.title || '';
            this.formContent = tmpl.content || '';
            this.updateLivePreview();
            this.showModal = true;
        },

        insertTag(tag) {
            const textarea = document.getElementById('templateContentTextarea');
            if (!textarea) {
                this.formContent += tag;
                this.updateLivePreview();
                return;
            }

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = this.formContent;
            this.formContent = text.substring(0, start) + tag + text.substring(end);
            this.updateLivePreview();

            this.$nextTick(() => {
                textarea.focus();
                textarea.setSelectionRange(start + tag.length, start + tag.length);
            });
        },

        updateLivePreview() {
            const dummy = {
                '{nama}': 'Ahmad Pratama',
                '{nis}': '12345678',
                '{kelas}': 'XII RPL 1',
                '{tanggal}': '{{ now()->format("d/m/Y") }}',
                '{jam_masuk}': '07:15',
                '{jam_pulang}': '15:30',
                '{durasi}': '8 jam 15 menit',
                '{durasi_terlambat}': '15 menit',
                '{durasi_pulang_cepat}': '30 menit',
                '{status}': 'Hadir',
                '{nama_sekolah}': '{{ auth()->user()->school?->name ?? "SMK Assuniyah" }}',
                '{diotorisasi}': 'Bpk. Budi Santoso',
                '{salam}': this.formCategory.includes('ortu') ? 'Yth. Orang Tua/Wali Ahmad Pratama,' : 'Halo, Ahmad Pratama 👋,',
                '{penutup}': this.formCategory.includes('ortu') ? '\n\nSilakan balas pesan ini dengan kata OK sebagai konfirmasi Anda.' : '\n\nSilakan balas pesan ini singkat saja (contoh: OK).'
            };

            let rendered = this.formContent || '';
            for (const [key, val] of Object.entries(dummy)) {
                rendered = rendered.replaceAll(key, val);
            }
            this.livePreviewText = rendered;
        },

        previewMessage(content, category) {
            const dummy = {
                '{nama}': 'Ahmad Pratama',
                '{nis}': '12345678',
                '{kelas}': 'XII RPL 1',
                '{tanggal}': '{{ now()->format("d/m/Y") }}',
                '{jam_masuk}': '07:15',
                '{jam_pulang}': '15:30',
                '{durasi}': '8 jam 15 menit',
                '{durasi_terlambat}': '15 menit',
                '{durasi_pulang_cepat}': '30 menit',
                '{status}': 'Hadir',
                '{nama_sekolah}': '{{ auth()->user()->school?->name ?? "SMK Assuniyah" }}',
                '{diotorisasi}': 'Bpk. Budi Santoso',
                '{salam}': category.includes('ortu') ? 'Yth. Orang Tua/Wali Ahmad Pratama,' : 'Halo, Ahmad Pratama 👋,',
                '{penutup}': category.includes('ortu') ? '\n\nSilakan balas pesan ini dengan kata OK sebagai konfirmasi Anda.' : '\n\nSilakan balas pesan ini singkat saja (contoh: OK).'
            };

            let rendered = content || '';
            for (const [key, val] of Object.entries(dummy)) {
                rendered = rendered.replaceAll(key, val);
            }
            this.previewModalText = rendered;
            this.showPreviewModal = true;
        },

        getDefaultSample(catKey) {
            if (catKey === 'checkin_siswa') {
                return "✅ *Konfirmasi Absen Masuk*\n\nHalo, *{nama}* 👋,\n\nTercatat masuk sekolah hari ini:\n📅 Tanggal : {tanggal}\n🕐 Jam Masuk: {jam_masuk}\n📊 Status : {status}\n🏫 Kelas : {kelas}\n\nSemangat belajar di {nama_sekolah}! ✨\n\n_Mohon balas OK untuk konfirmasi tanda terima._";
            } else if (catKey === 'checkin_ortu') {
                return "✅ *Laporan Kehadiran Siswa*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPutra/putri Anda telah tercatat hadir di sekolah:\n📅 Tanggal : {tanggal}\n🕐 Jam Masuk: {jam_masuk}\n📊 Status : {status}\n🏫 Kelas : {kelas}\n\nTerima kasih atas perhatian Bapak/Ibu. 🙏\n\n_Mohon balas pesan ini dengan kata OK sebagai tanda laporan telah dibaca._";
            } else if (catKey === 'late_siswa') {
                return "⚠️ *Notifikasi Terlambat*\n\nHalo, *{nama}* 👋,\n\nAnda terdeteksi absen masuk melebihi batas waktu:\n📅 Tanggal : {tanggal}\n🕐 Jam Masuk: {jam_masuk}\n⏰ Keterlambatan: {durasi_terlambat}\n🏫 Kelas : {kelas}\n\nMohon lebih disiplin waktu kedepannya ya. 🙏";
            } else if (catKey === 'late_ortu') {
                return "⚠️ *Laporan Keterlambatan Siswa*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPutra/putri Anda tiba di sekolah dan tercatat terlambat:\n📅 Tanggal : {tanggal}\n🕐 Jam Masuk: {jam_masuk}\n⏰ Keterlambatan: {durasi_terlambat}\n🏫 Kelas : {kelas}\n\nMohon bantuannya untuk mengingatkan anak. 🙏";
            } else if (catKey === 'checkout_siswa') {
                return "🏠 *Notifikasi Absen Pulang*\n\nHalo, *{nama}* 👋,\n\nTercatat telah menyelesaikan KBM dan absen pulang:\n📅 Tanggal : {tanggal}\n🕐 Jam Pulang: {jam_pulang}\n⏱️ Durasi di Sekolah: {durasi}\n\nHati-hati di jalan saat perjalanan pulang! 🏠";
            } else if (catKey === 'checkout_ortu') {
                return "🏠 *Laporan Absen Pulang Siswa*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPutra/putri Anda telah selesai mengikuti pelajaran dan melakukan absen pulang:\n📅 Tanggal : {tanggal}\n🕐 Jam Pulang: {jam_pulang}\n⏱️ Durasi : {durasi}\n\nSemoga selamat sampai di rumah. 🤝";
            } else if (catKey === 'early_checkout_siswa') {
                return "🚪 *Notifikasi Pulang Cepat*\n\nHalo, *{nama}* 👋,\n\nAnda tercatat melakukan absen pulang sebelum jam kepulangan resmi:\n📅 Tanggal : {tanggal}\n🕐 Jam Pulang: {jam_pulang}\n⏱️ Selisih Waktu: {durasi_pulang_cepat}\n\nHati-hati di jalan! 🏠";
            } else if (catKey === 'early_checkout_ortu') {
                return "🚪 *Laporan Pulang Cepat Siswa*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPutra/putri Anda tercatat melakukan absen pulang lebih awal:\n📅 Tanggal : {tanggal}\n🕐 Jam Pulang: {jam_pulang}\n⏱️ Selisih Waktu: {durasi_pulang_cepat}\n🏫 Kelas : {kelas}\n\nMohon konfirmasi ke pihak sekolah jika terdapat hal darurat. 🙏";
            } else if (catKey === 'izin_siswa') {
                return "📝 *Konfirmasi Izin Sekolah*\n\nHalo, *{nama}* 👋,\n\nPengajuan izin Anda telah diterima oleh pihak sekolah:\n📅 Tanggal : {tanggal}\n📊 Status : Izin\n🏫 Kelas : {kelas}";
            } else if (catKey === 'izin_ortu') {
                return "📝 *Laporan Izin Siswa*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPermohonan izin putra/putri Anda telah tercatat dalam sistem absensi:\n📅 Tanggal : {tanggal}\n🏫 Kelas : {kelas}\n📊 Keterangan: Izin\n\nTerima kasih atas informasinya. 🙏";
            } else if (catKey === 'sakit_siswa') {
                return "🤒 *Pemberitahuan Izin Sakit*\n\nHalo, *{nama}* 👋,\n\nStatus absensi Anda tercatat Sakit hari ini:\n📅 Tanggal : {tanggal}\n🏫 Kelas : {kelas}\n\nIstirahat yang cukup dan semoga lekas sembuh ya! ❤️";
            } else if (catKey === 'sakit_ortu') {
                return "🤒 *Laporan Siswa Sakit*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPutra/putri Anda tercatat izin tidak masuk sekolah karena Sakit:\n📅 Tanggal : {tanggal}\n🏫 Kelas : {kelas}\n\nKami mendoakan semoga lekas pulih dan sehat kembali. 🤲";
            } else if (catKey === 'alpha_siswa') {
                return "❌ *Pemberitahuan Ketidakhadiran*\n\nHalo, *{nama}* 👋,\n\n📅 Tanggal: {tanggal}\n📊 Status: {status}\n\nAnda tercatat tidak hadir di sekolah hari ini tanpa keterangan. Segera hubungi wali kelas Anda.";
            } else if (catKey === 'alpha_ortu') {
                return "❌ *Laporan Ketidakhadiran Siswa*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPutra/putri Anda terdata tidak hadir di sekolah hari ini tanpa keterangan:\n📅 Tanggal: {tanggal}\n🏫 Kelas : {kelas}\n📊 Status : Alpha\n\nMohon segera konfirmasi ke wali kelas jika sedang berhalangan hadir. 🤝";
            } else if (catKey === 'bolos_siswa') {
                return "🏃 *Pemberitahuan Indikasi Bolos*\n\nHalo, *{nama}* 👋,\n\n📅 Tanggal: {tanggal}\n📊 Status: Bolos (Tidak Absen Pulang)\n\nAnda memiliki riwayat absen masuk tetapi tidak menempelkan kartu saat jam kepulangan.";
            } else if (catKey === 'bolos_ortu') {
                return "🏃 *Laporan Indikasi Bolos Siswa*\n\nYth. Orang Tua/Wali dari *{nama}*,\n\nPutra/putri Anda terdeteksi tidak melakukan absen keluar/pulang hari ini:\n📅 Tanggal: {tanggal}\n🏫 Kelas : {kelas}\n\nMohon konfirmasi keberadaan anak kepada wali kelas. 🤝";
            }
            return "📢 *Pemberitahuan Absensi*\n\nHalo, *{nama}* 👋,\n\n📅 Tanggal: {tanggal}\n👤 Nama : {nama}\n🏫 Kelas : {kelas}";
        }
    };
}
</script>
@endsection
