{{-- Notification Dropdown Component --}}
@php
    $latestAnnouncement = \App\Models\Announcement::where('is_active', true)->latest()->first();
    $latestAnnouncementId = $latestAnnouncement?->id ?? 0;
@endphp
<div class="relative" x-data="{
    dropdownOpen: false,
    notifying: false,
    latestId: {{ $latestAnnouncementId }},
    init() {
        if (this.latestId > 0) {
            const readId = parseInt(localStorage.getItem('notif_read_id') || '0');
            this.notifying = readId < this.latestId;
        }
    },
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
        if (this.dropdownOpen) {
            this.markRead();
        }
    },
    markRead() {
        if (this.notifying) {
            this.notifying = false;
            localStorage.setItem('notif_read_id', this.latestId);
        }
    },
    closeDropdown() {
        this.dropdownOpen = false;
    }
}" @click.away="closeDropdown()">
    <!-- Notification Button -->
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        type="button"
        title="Pengumuman & Update Rilis"
    >
        <!-- Notification Badge -->
        <span
            x-show="notifying"
            class="absolute right-0 top-0.5 z-1 h-2.5 w-2.5 rounded-full bg-brand-500"
            style="display: none;"
        >
            <span
                class="absolute inline-flex w-full h-full bg-brand-500 rounded-full opacity-75 -z-1 animate-ping"
            ></span>
        </span>

        <!-- Bell Icon -->
        <svg
            class="fill-current"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                fill=""
            />
        </svg>
    </button>

    <!-- Dropdown Start -->
    <div
        x-show="dropdownOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute -right-[160px] mt-[17px] flex h-[480px] w-[340px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[360px] lg:right-0 z-50"
        style="display: none;"
    >
        <!-- Dropdown Header -->
        <div class="flex items-center justify-between pb-3 px-2 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <i class="fas fa-bullhorn text-xs"></i>
                </span>
                <h5 class="text-sm font-bold text-gray-800 dark:text-white/90">Pengumuman & Update</h5>
            </div>

            <button @click="closeDropdown()" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-white transition" type="button" title="Tutup">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Notification List -->
        <ul class="flex flex-col h-auto max-h-[400px] overflow-y-auto custom-scrollbar divide-y divide-gray-100 dark:divide-gray-800" x-data="{ selected: null }">
            @php
                $announcements = \App\Models\Announcement::where('is_active', true)->latest()->take(10)->get();
            @endphp

            @if($announcements->count() > 0)
                @foreach ($announcements as $index => $announcement)
                    <li class="p-2">
                        <div 
                            @click="selected !== {{ $index }} ? selected = {{ $index }} : selected = null"
                            class="flex w-full flex-col gap-2 p-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-white/5 transition-colors cursor-pointer"
                        >
                            <div class="flex items-start justify-between w-full gap-2">
                                <div class="flex items-start gap-2.5">
                                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border {{ $announcement->type_badge_class }}">
                                        <i class="fas {{ $announcement->type_icon }} text-xs"></i>
                                    </div>
                                    <div class="text-left">
                                        <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded border {{ $announcement->type_badge_class }}">
                                                {{ $announcement->type_label }}
                                            </span>
                                            @if($announcement->version)
                                                <span class="text-[10px] font-mono font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded">
                                                    {{ $announcement->version }}
                                                </span>
                                            @endif
                                        </div>
                                        <h6 class="text-xs font-semibold text-gray-800 dark:text-white/90 leading-tight">
                                            {{ $announcement->title }}
                                        </h6>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                                            {{ $announcement->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 transition-transform mt-1 text-xs" :class="selected === {{ $index }} ? 'rotate-180' : ''"></i>
                            </div>
                            
                            <!-- Expandable Content -->
                            <div x-show="selected === {{ $index }}" x-collapse class="w-full text-left mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-600 dark:text-gray-300 space-y-2">
                                <div class="whitespace-pre-line leading-relaxed">
                                    {!! nl2br(e($announcement->content)) !!}
                                </div>
                                <div class="flex items-center justify-between pt-2">
                                    @if($announcement->action_url)
                                        <a href="{{ $announcement->action_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-500 hover:underline">
                                            <span>{{ $announcement->action_text ?: 'Pelajari Selengkapnya' }}</span>
                                            <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                                        </a>
                                    @else
                                        <span></span>
                                    @endif

                                    <button
                                        @click.stop="window.dispatchEvent(new CustomEvent('open-announcement-modal', { detail: { id: {{ $announcement->id }} } })); closeDropdown()"
                                        type="button"
                                        class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400"
                                    >
                                        <i class="fas fa-expand text-[9px]"></i> Buka Pop-up
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            @else
                <li class="p-6 text-center">
                    <i class="fas fa-bell-slash text-2xl text-gray-300 dark:text-gray-600 mb-2 block"></i>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada pengumuman saat ini.</p>
                </li>
            @endif
        </ul>
    </div>
    <!-- Dropdown End -->
</div>
