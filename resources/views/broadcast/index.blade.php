@extends('layouts.app')

@section('title', 'Broadcast Pesan Massal')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-bullhorn text-brand-500 mr-2"></i> Broadcast Pesan Massal
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Kirim pengumuman dan informasi sekolah secara massal melalui WhatsApp dan Telegram.
        </p>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
    <div class="xl:col-span-3">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h6 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    <i class="fas fa-paper-plane text-brand-500"></i> Form Buat Pesan Broadcast
                </h6>
            </div>
            
            <div class="p-6">
                @if(session('success'))
                    <div class="mb-6 flex w-full border-l-6 border-success bg-success-50 px-6 py-4 shadow-sm rounded-r-xl dark:bg-success-500/10">
                        <div class="mr-4 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-success text-white">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="w-full">
                            <h5 class="mb-1 font-semibold text-[#004434] dark:text-[#34D399]">Berhasil</h5>
                            <p class="text-sm text-[#004434] dark:text-[#34D399] whitespace-pre-line">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="mb-6 flex w-full border-l-6 border-error bg-error-50 px-6 py-4 shadow-sm rounded-r-xl dark:bg-error-500/10">
                        <div class="mr-4 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-error text-white">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="w-full">
                            <h5 class="mb-1 font-semibold text-[#B45454] dark:text-[#F87171]">Gagal</h5>
                            <p class="text-sm text-[#B45454] dark:text-[#F87171]">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 rounded-xl border border-error/20 bg-error-50 p-4 text-sm text-error dark:bg-error-500/10">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('broadcast.send') }}" method="POST" id="broadcastForm" onsubmit="return confirmSubmit(event);">
                    @csrf

                    <!-- 1. PILIH KELAS TARGET -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">1</span>
                                Pilih Kelas Target
                            </label>
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400" id="selectedCountText">0 kelas dipilih</span>
                        </div>
                        
                        <div class="mb-3.5 rounded-xl border border-brand-200 bg-brand-50/60 p-3 dark:border-brand-500/20 dark:bg-brand-500/10 transition hover:bg-brand-50 dark:hover:bg-brand-500/15">
                            <label class="flex items-center gap-3 cursor-pointer select-none">
                                <input type="checkbox" id="checkAll" class="h-4.5 w-4.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 cursor-pointer">
                                <span class="font-bold text-brand-600 dark:text-brand-400 text-sm flex items-center gap-1.5">
                                    <i class="fas fa-bullhorn text-xs"></i> PILIH SEMUA KELAS
                                </span>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 max-h-60 overflow-y-auto p-1.5 rounded-xl border border-gray-100 dark:border-gray-800/80 bg-gray-50/40 dark:bg-gray-900/30">
                            @forelse($kelas as $k)
                                <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg border border-gray-200/80 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/80 cursor-pointer select-none transition shadow-2xs has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50 dark:has-[:checked]:border-brand-500/60 dark:has-[:checked]:bg-brand-500/15">
                                    <input type="checkbox" name="target_class_ids[]" value="{{ $k->id }}" id="kelas_{{ $k->id }}" class="class-checkbox h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 cursor-pointer">
                                    <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ $k->nama_kelas }}</span>
                                </label>
                            @empty
                                <div class="col-span-full py-4 text-center text-sm text-gray-400">
                                    Tidak ada data kelas ditemukan.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- 2. PILIH SALURAN & TARGET PENERIMA (2 Kolom) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 pt-6 border-t border-gray-150 dark:border-gray-800">
                        <!-- Saluran Broadcast -->
                        <div>
                            <label class="mb-3 block text-sm font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">2</span>
                                Saluran Pengiriman (Channel)
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <!-- WhatsApp -->
                                <label class="channel-card flex flex-col items-center justify-center p-3 rounded-xl border-2 border-brand-500 bg-brand-50/20 dark:bg-brand-500/10 cursor-pointer text-center transition" id="card_channel_wa">
                                    <input type="radio" name="channel" value="wa" class="sr-only" checked onchange="handleChannelChange()">
                                    <i class="fab fa-whatsapp text-2xl text-green-500 mb-1.5"></i>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white">WhatsApp</span>
                                </label>
                                <!-- Telegram -->
                                <label class="channel-card flex flex-col items-center justify-center p-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 cursor-pointer text-center transition" id="card_channel_tele">
                                    <input type="radio" name="channel" value="tele" class="sr-only" onchange="handleChannelChange()">
                                    <i class="fab fa-telegram-plane text-2xl text-blue-500 mb-1.5"></i>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white">Telegram</span>
                                </label>
                                <!-- Keduanya -->
                                <label class="channel-card flex flex-col items-center justify-center p-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 cursor-pointer text-center transition" id="card_channel_both">
                                    <input type="radio" name="channel" value="both" class="sr-only" onchange="handleChannelChange()">
                                    <div class="flex items-center gap-1 text-lg mb-1.5">
                                        <i class="fab fa-whatsapp text-green-500"></i>
                                        <span class="text-xs text-gray-400">+</span>
                                        <i class="fab fa-telegram-plane text-blue-500"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white">Keduanya</span>
                                </label>
                            </div>
                        </div>

                        <!-- Target Penerima -->
                        <div>
                            <label class="mb-3 block text-sm font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">3</span>
                                Target Penerima
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <!-- Siswa -->
                                <label class="recipient-card flex flex-col items-center justify-center p-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 cursor-pointer text-center transition" id="card_rec_siswa">
                                    <input type="radio" name="target_recipient" value="siswa" class="sr-only" onchange="handleRecipientChange()">
                                    <i class="fas fa-user-graduate text-2xl text-amber-500 mb-1.5"></i>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white">Siswa</span>
                                </label>
                                <!-- Ortu -->
                                <label class="recipient-card flex flex-col items-center justify-center p-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 cursor-pointer text-center transition" id="card_rec_ortu">
                                    <input type="radio" name="target_recipient" value="ortu" class="sr-only" onchange="handleRecipientChange()">
                                    <i class="fas fa-user-shield text-2xl text-purple-500 mb-1.5"></i>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white">Orang Tua</span>
                                </label>
                                <!-- Keduanya -->
                                <label class="recipient-card flex flex-col items-center justify-center p-3 rounded-xl border-2 border-brand-500 bg-brand-50/20 dark:bg-brand-500/10 cursor-pointer text-center transition" id="card_rec_both">
                                    <input type="radio" name="target_recipient" value="both" class="sr-only" checked onchange="handleRecipientChange()">
                                    <i class="fas fa-users text-2xl text-brand-500 mb-1.5"></i>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white">Keduanya</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 3. ISI PESAN & PLACEHOLDERS -->
                    <div class="mb-8 pt-6 border-t border-gray-150 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <label for="message" class="text-sm font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">4</span>
                                Isi Pesan Pengumuman
                            </label>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Gunakan tag variabel di bawah</span>
                        </div>

                        <!-- Variable Chips -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Klik tag untuk menyisipkan:</span>
                            <button type="button" onclick="insertTag('{nama}')" class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/20 dark:hover:text-brand-400 transition">
                                <code>{nama}</code> (Nama Siswa)
                            </button>
                            <button type="button" onclick="insertTag('{kelas}')" class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/20 dark:hover:text-brand-400 transition">
                                <code>{kelas}</code> (Nama Kelas)
                            </button>
                            <button type="button" onclick="insertTag('{nis}')" class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/20 dark:hover:text-brand-400 transition">
                                <code>{nis}</code> (NIS)
                            </button>
                            <button type="button" onclick="insertTag('{sekolah}')" class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/20 dark:hover:text-brand-400 transition">
                                <code>{sekolah}</code> (Nama Sekolah)
                            </button>
                        </div>

                        <textarea name="message" id="message" rows="6" required placeholder="Tuliskan isi pengumuman atau informasi sekolah di sini..." class="w-full rounded-xl border border-gray-200 bg-transparent px-5 py-3 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:focus:border-brand-500 text-gray-800 dark:text-white/90 transition shadow-theme-xs text-sm leading-relaxed" oninput="updatePreview()"></textarea>
                        
                        <!-- Format Tips & Live Preview Toggle -->
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-lg bg-gray-50 p-3.5 dark:bg-gray-800/50">
                                <h6 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                                    <i class="fas fa-info-circle text-brand-500"></i> Format Teks Didukung:
                                </h6>
                                <ul class="flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <li><code>*teks tebal*</code> → <strong>teks tebal</strong></li>
                                    <li><code>_teks miring_</code> → <em>teks miring</em></li>
                                    <li>Header pembuka dan identitas penerima akan disisipkan otomatis.</li>
                                </ul>
                            </div>

                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-3.5 shadow-sm">
                                <h6 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                                    <i class="fas fa-eye text-brand-500"></i> Preview Tampilan Pesan:
                                </h6>
                                <div id="messagePreview" class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line font-mono bg-gray-50 dark:bg-gray-800/80 p-2.5 rounded border border-gray-100 dark:border-gray-800 max-h-36 overflow-y-auto">
                                    [Tulis pesan di atas untuk melihat preview]
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <button type="submit" id="submitBtn" class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 font-semibold text-white hover:bg-brand-600 transition shadow-md shadow-brand-500/20 active:scale-[0.99]">
                        <i class="fas fa-paper-plane"></i> Kirim Broadcast Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- SIDE PANEL TIPS & INFO -->
    <div class="xl:col-span-1 space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark p-5">
            <h6 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2 border-b border-gray-150 dark:border-gray-800 pb-3 mb-4">
                <i class="fas fa-lightbulb text-warning-500"></i> Panduan Broadcast
            </h6>
            
            <div class="space-y-4 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                <div>
                    <h5 class="font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-1.5">
                        <i class="fab fa-whatsapp text-green-500"></i> Saluran WhatsApp
                    </h5>
                    <p>Pesan otomatis masuk antrean dengan delay awal acak (1–15 menit), jeda dinamis (8–15 detik), dan pembatasan kuota per jam untuk proteksi anti-ban maksimal.</p>
                </div>

                <div>
                    <h5 class="font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-1.5">
                        <i class="fab fa-telegram-plane text-blue-500"></i> Saluran Telegram
                    </h5>
                    <p>Pesan dikirimkan ke Telegram Siswa / Orang Tua yang telah mengaktifkan bot sekolah dan terdaftar chat ID-nya.</p>
                </div>

                <div>
                    <h5 class="font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-brand-500"></i> Filter Last Seen Otomatis
                    </h5>
                    <p>Sistem otomatis memfilter kontak yang masih aktif berinteraksi dengan gateway sekolah sesuai aturan sistem.</p>
                </div>

                <div class="rounded-lg bg-warning-50 p-3 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400 border border-warning-200 dark:border-warning-500/20">
                    <strong>Perhatian:</strong> Pesan yang sudah masuk ke dalam antrean broadcast tidak dapat dibatalkan secara manual.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Checkbox Logic */
    input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
        background-color: #3C50E0;
    }
    input[type="checkbox"]:checked ~ .box span {
        opacity: 1;
        color: white;
    }
    .dark input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
        background-color: #3C50E0;
    }
</style>
@endsection

@push('scripts')
<script>
    // 1. Check All Checkboxes
    const checkAllBox = document.getElementById('checkAll');
    const classCheckboxes = document.querySelectorAll('.class-checkbox');
    const selectedCountText = document.getElementById('selectedCountText');

    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.class-checkbox:checked').length;
        selectedCountText.textContent = checkedCount + ' kelas dipilih';
        if (checkAllBox) {
            checkAllBox.checked = (checkedCount > 0 && checkedCount === classCheckboxes.length);
        }
    }

    if (checkAllBox) {
        checkAllBox.addEventListener('change', function () {
            classCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });
    }

    classCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    // 2. Channel Card Selection Style
    function handleChannelChange() {
        const selectedChannel = document.querySelector('input[name="channel"]:checked').value;
        const channels = ['wa', 'tele', 'both'];
        
        channels.forEach(ch => {
            const card = document.getElementById('card_channel_' + ch);
            if (card) {
                if (ch === selectedChannel) {
                    card.classList.add('border-brand-500', 'bg-brand-50/20', 'dark:bg-brand-500/10');
                    card.classList.remove('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-900');
                } else {
                    card.classList.remove('border-brand-500', 'bg-brand-50/20', 'dark:bg-brand-500/10');
                    card.classList.add('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-900');
                }
            }
        });
    }

    // 3. Recipient Card Selection Style
    function handleRecipientChange() {
        const selectedRec = document.querySelector('input[name="target_recipient"]:checked').value;
        const recs = ['siswa', 'ortu', 'both'];
        
        recs.forEach(r => {
            const card = document.getElementById('card_rec_' + r);
            if (card) {
                if (r === selectedRec) {
                    card.classList.add('border-brand-500', 'bg-brand-50/20', 'dark:bg-brand-500/10');
                    card.classList.remove('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-900');
                } else {
                    card.classList.remove('border-brand-500', 'bg-brand-50/20', 'dark:bg-brand-500/10');
                    card.classList.add('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-900');
                }
            }
        });
        updatePreview();
    }

    // 4. Insert Variable Tag into Textarea
    function insertTag(tag) {
        const textarea = document.getElementById('message');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        
        textarea.value = text.substring(0, start) + tag + text.substring(end);
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + tag.length;
        updatePreview();
    }

    // 5. Live Message Preview
    function updatePreview() {
        const msg = document.getElementById('message').value;
        const previewBox = document.getElementById('messagePreview');
        const targetRec = document.querySelector('input[name="target_recipient"]:checked')?.value || 'both';

        if (!msg.trim()) {
            previewBox.textContent = '[Tulis pesan di atas untuk melihat preview]';
            return;
        }

        let recipientLabel = (targetRec === 'ortu') ? 'Orang Tua / Wali dari Ahmad' : (targetRec === 'siswa' ? 'Ahmad' : 'Ahmad / Orang Tua');
        let rendered = "📢 PENGUMUMAN SEKOLAH\nKepada: " + recipientLabel + "\nKelas: X-A\n\n";
        
        let body = msg
            .replace(/\{nama\}/g, 'Ahmad')
            .replace(/\{penerima\}/g, recipientLabel)
            .replace(/\{kelas\}/g, 'X-A')
            .replace(/\{nis\}/g, '12345')
            .replace(/\{sekolah\}/g, 'Nama Sekolah');

        rendered += body + "\n\n_Dikirim otomatis oleh Sistem_";
        previewBox.textContent = rendered;
    }

    // 6. Confirm Submit Alert
    function confirmSubmit(event) {
        const checkedClasses = document.querySelectorAll('.class-checkbox:checked').length;
        if (checkedClasses === 0) {
            alert('Silakan pilih minimal 1 kelas target!');
            event.preventDefault();
            return false;
        }

        const channel = document.querySelector('input[name="channel"]:checked').value;
        const channelLabel = channel === 'wa' ? 'WhatsApp' : (channel === 'tele' ? 'Telegram' : 'WhatsApp & Telegram');
        
        return confirm('Apakah Anda yakin ingin mengirimkan broadcast ini melalui saluran ' + channelLabel + ' ke ' + checkedClasses + ' kelas terpilih?');
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateSelectedCount();
        handleChannelChange();
        handleRecipientChange();
    });
</script>
@endpush