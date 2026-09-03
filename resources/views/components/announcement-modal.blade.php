@props(['announcements' => collect()])

@if($announcements->isNotEmpty())
<div
    x-data="announcementModal(@js($announcements->values()))"
    x-init="initModal()"
    @open-announcement-modal.window="openSpecificModal($event.detail)"
    x-cloak
>
    <!-- Modal Backdrop & Dialog -->
    <div
        x-show="isOpen"
        class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto bg-black/70 p-4 backdrop-blur-sm transition-opacity duration-300 sm:p-6"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none;"
    >
        <!-- Modal Card Container -->
        <div
            @click.away="closeModal(false)"
            class="relative w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 transform transition-all duration-300"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        >
            <!-- Header with Dynamic Gradient -->
            <div
                class="relative p-6 sm:p-8 text-white overflow-hidden"
                :style="getHeaderGradientStyle(currentAnnouncement?.type)"
            >
                <!-- Decorative Background Pattern -->
                <div class="absolute -right-4 -bottom-6 opacity-15 pointer-events-none text-8xl sm:text-9xl">
                    <i class="fas" :class="getTypeIcon(currentAnnouncement?.type)"></i>
                </div>

                <!-- Close Button Top Right -->
                <button
                    @click="closeModal(false)"
                    type="button"
                    class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-black/20 text-white hover:bg-black/35 backdrop-blur-md transition-all focus:outline-none z-10"
                    title="Tutup"
                >
                    <i class="fas fa-times text-sm"></i>
                </button>

                <!-- Category & Version Badge -->
                <div class="flex flex-wrap items-center gap-2 mb-3 relative z-10 pr-12">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur-md text-white shadow-xs border border-white/25">
                        <i class="fas text-xs" :class="getTypeIcon(currentAnnouncement?.type)"></i>
                        <span x-text="getTypeLabel(currentAnnouncement?.type)"></span>
                    </span>

                    <template x-if="currentAnnouncement?.version">
                        <span class="inline-flex items-center gap-1 rounded-full bg-black/30 px-2.5 py-1 text-xs font-mono font-bold text-white backdrop-blur-md border border-white/15">
                            <i class="fas fa-tag text-[10px] text-amber-300"></i>
                            <span x-text="currentAnnouncement?.version"></span>
                        </span>
                    </template>

                    <span class="text-xs text-white/90 ml-auto font-medium" x-text="formatDate(currentAnnouncement?.created_at)"></span>
                </div>

                <!-- Title -->
                <h3 class="text-xl sm:text-2xl font-bold tracking-tight text-white drop-shadow-sm pr-10 relative z-10 leading-snug" x-text="currentAnnouncement?.title"></h3>
            </div>

            <!-- Modal Body / Content -->
            <div class="p-6 sm:p-8 max-h-[55vh] overflow-y-auto custom-scrollbar">
                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 text-sm leading-relaxed space-y-3">
                    <div class="text-sm leading-relaxed font-sans" x-html="renderFormattedContent(currentAnnouncement?.content)"></div>
                </div>

                <!-- Multiple announcements pagination pills if > 1 -->
                <template x-if="items.length > 1">
                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <template x-for="(item, idx) in items" :key="item.id">
                                <button
                                    @click="currentIndex = idx"
                                    type="button"
                                    class="h-2 rounded-full transition-all duration-200"
                                    :class="currentIndex === idx ? 'w-6 bg-brand-500' : 'w-2 bg-gray-300 dark:bg-gray-700 hover:bg-gray-400'"
                                ></button>
                            </template>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="prevItem()"
                                :disabled="currentIndex === 0"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <span class="text-xs text-gray-400" x-text="`${currentIndex + 1} dari ${items.length}`"></span>
                            <button
                                @click="nextItem()"
                                :disabled="currentIndex === items.length - 1"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 dark:bg-gray-800/60 px-6 py-4 sm:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-800">
                <!-- Dismiss Checkbox / Button -->
                <button
                    @click="dismissForever()"
                    type="button"
                    class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition"
                >
                    <i class="far fa-check-circle text-sm text-brand-500"></i>
                    <span>Jangan tampilkan lagi</span>
                </button>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                    <template x-if="currentAnnouncement?.action_url">
                        <a
                            :href="currentAnnouncement.action_url"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gray-200 px-4 py-2.5 text-xs font-semibold text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 transition shadow-xs"
                        >
                            <span x-text="currentAnnouncement.action_text || 'Pelajari Selengkapnya'"></span>
                            <i class="fas fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    </template>

                    <button
                        @click="closeModal(false)"
                        type="button"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition"
                    >
                        <span>Mengerti & Tutup</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function announcementModal(announcements) {
        return {
            items: announcements || [],
            currentIndex: 0,
            isOpen: false,

            get currentAnnouncement() {
                return this.items[this.currentIndex] || null;
            },

            initModal() {
                if (this.items.length === 0) return;

                // Check if any popup announcement is not yet dismissed by this browser
                const unreadIndex = this.items.findIndex(item => {
                    return !localStorage.getItem('announcement_dismissed_' + item.id);
                });

                if (unreadIndex !== -1) {
                    this.currentIndex = unreadIndex;
                    // Open automatically with slight delay for smooth landing
                    setTimeout(() => {
                        this.isOpen = true;
                    }, 400);
                }
            },

            openSpecificModal(detail) {
                if (detail && detail.id) {
                    const foundIndex = this.items.findIndex(i => i.id === detail.id);
                    if (foundIndex !== -1) {
                        this.currentIndex = foundIndex;
                    }
                }
                this.isOpen = true;
            },

            nextItem() {
                if (this.currentIndex < this.items.length - 1) {
                    this.currentIndex++;
                }
            },

            prevItem() {
                if (this.currentIndex > 0) {
                    this.currentIndex--;
                }
            },

            closeModal(dismiss = false) {
                if (dismiss && this.currentAnnouncement) {
                    localStorage.setItem('announcement_dismissed_' + this.currentAnnouncement.id, 'true');
                }
                this.isOpen = false;
            },

            dismissForever() {
                if (this.currentAnnouncement) {
                    localStorage.setItem('announcement_dismissed_' + this.currentAnnouncement.id, 'true');
                }
                this.isOpen = false;
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            getHeaderGradientStyle(type) {
                switch(type) {
                    case 'release':
                        return 'background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 50%, #2563eb 100%);';
                    case 'feature':
                        return 'background: linear-gradient(135deg, #059669 0%, #0d9488 100%);';
                    case 'warning':
                        return 'background: linear-gradient(135deg, #d97706 0%, #ea580c 100%);';
                    case 'maintenance':
                        return 'background: linear-gradient(135deg, #2563eb 0%, #0284c7 100%);';
                    default:
                        return 'background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);';
                }
            },

            getTypeIcon(type) {
                switch(type) {
                    case 'release': return 'fa-rocket';
                    case 'feature': return 'fa-sparkles';
                    case 'warning': return 'fa-triangle-exclamation';
                    case 'maintenance': return 'fa-screwdriver-wrench';
                    default: return 'fa-bullhorn';
                }
            },

            getTypeLabel(type) {
                switch(type) {
                    case 'release': return 'Update Rilis';
                    case 'feature': return 'Fitur Baru';
                    case 'warning': return 'Pemberitahuan Penting';
                    case 'maintenance': return 'Pemeliharaan Sistem';
                    default: return 'Informasi';
                }
            },

            renderFormattedContent(text) {
                if (!text) return '';
                
                // Unescape any literal \r\n or \n characters
                let processed = text.replace(/\\r\\n|\\n|\\r/g, '\n');

                // Escape HTML
                let escaped = processed
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;");

                // Normalize line breaks
                const lines = escaped.split(/\r?\n/);
                let html = '';
                let inList = false;

                for (let i = 0; i < lines.length; i++) {
                    let line = lines[i].trim();
                    if (!line) {
                        if (inList) {
                            html += '</ul>';
                            inList = false;
                        }
                        html += '<div class="h-2"></div>';
                        continue;
                    }

                    // Check bullet point
                    const bulletMatch = line.match(/^[•\-\*]\s*(.+)$/);
                    if (bulletMatch) {
                        if (!inList) {
                            html += '<ul class="my-2 space-y-2 bg-gray-50 dark:bg-gray-800/60 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">';
                            inList = true;
                        }
                        let itemContent = bulletMatch[1].replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                        html += `<li class="flex items-start gap-2.5 text-xs sm:text-sm text-gray-700 dark:text-gray-300"><span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 text-[10px] font-bold mt-0.5"><i class="fas fa-check text-[9px]"></i></span><span>${itemContent}</span></li>`;
                    } else {
                        if (inList) {
                            html += '</ul>';
                            inList = false;
                        }
                        let pContent = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                        html += `<p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-2">${pContent}</p>`;
                    }
                }

                if (inList) {
                    html += '</ul>';
                }

                return html;
            }
        };
    }
</script>
@endif
