<div class="relative" x-data="{
    dropdownOpen: false,
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },
    closeDropdown() {
        this.dropdownOpen = false;
    }
}" @click.away="closeDropdown()">
    <!-- User Button -->
    <button class="flex items-center text-gray-700 dark:text-gray-400" @click.prevent="toggleDropdown()" type="button">
        <span
            class="mr-3 overflow-hidden rounded-full h-11 w-11 flex items-center justify-center bg-gray-100 dark:bg-gray-800 shrink-0">
            @php
                $avatarUrl = null;
                $words = explode(' ', trim(auth()->user()->full_name));
                $avatarInitials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

                if (auth()->check() && auth()->user()->school && auth()->user()->school->logo) {
                    $logoPath = auth()->user()->school->logo;
                    $isStorage = \Illuminate\Support\Str::startsWith($logoPath, 'schools/');
                    if ($isStorage && file_exists(storage_path('app/public/' . $logoPath))) {
                        $avatarUrl = asset('storage/' . $logoPath);
                    } elseif (!$isStorage && file_exists(public_path('img/' . $logoPath))) {
                        $avatarUrl = asset('img/' . $logoPath);
                    }
                } elseif (auth()->check() && auth()->user()->isSuperAdmin()) {
                    $globalLogo = \App\Models\Setting::where('school_id', 0)->where('setting_key', 'logo_filename')->value('setting_value');
                    if ($globalLogo) {
                        $isStorage = \Illuminate\Support\Str::startsWith($globalLogo, 'schools/');
                        if ($isStorage && file_exists(storage_path('app/public/' . $globalLogo))) {
                            $avatarUrl = asset('storage/' . $globalLogo);
                        } elseif (!$isStorage && file_exists(public_path('img/' . $globalLogo))) {
                            $avatarUrl = asset('img/' . $globalLogo);
                        }
                    }
                }
            @endphp
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="User" class="w-full h-full object-cover" />
            @else
                <span class="text-sm font-bold text-brand-500 dark:text-brand-400 select-none">{{ $avatarInitials }}</span>
            @endif

        </span>

        <span class="block mr-1 font-medium text-theme-sm">{{ auth()->user()->full_name }}</span>

        <!-- Chevron Icon -->
        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': dropdownOpen }" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- Dropdown Start -->
    <div x-show="dropdownOpen" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark z-50"
        style="display: none;">
        <!-- User Info -->
        <div>
            <span
                class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">{{ auth()->user()->full_name }}</span>
            <span
                class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->username }}</span>
        </div>

        <!-- Menu Items -->
        <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800">
            @php
                $menuItems = [
                    [
                        'text' => 'Edit profile',
                        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                fill-rule="evenodd"
                                                clip-rule="evenodd"
                                                d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25ZM8.48126 9.26784C8.48126 7.32499 10.0563 5.75 11.9991 5.75C13.9419 5.75 15.5169 7.32499 15.5169 9.26784C15.5169 11.2107 13.9419 12.7857 11.9991 12.7857C10.0563 12.7857 8.48126 11.2107 8.48126 9.26784Z"
                                                fill="currentColor"
                                            />
                                        </svg>',
                        'path' => route('profile.edit'),
                    ],

                    [
                        'text' => 'Support',
                        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                          <path
                                            fill-rule="evenodd"
                                            clip-rule="evenodd"
                                            d="M3.5 12C3.5 7.30558 7.30558 3.5 12 3.5C16.6944 3.5 20.5 7.30558 20.5 12C20.5 16.6944 16.6944 20.5 12 20.5C7.30558 20.5 3.5 16.6944 3.5 12ZM12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM11.0991 7.52507C11.0991 8.02213 11.5021 8.42507 11.9991 8.42507H12.0001C12.4972 8.42507 12.9001 8.02213 12.9001 7.52507C12.9001 7.02802 12.4972 6.62507 12.0001 6.62507H11.9991C11.5021 6.62507 11.0991 7.02802 11.0991 7.52507ZM12.0001 17.3714C11.5859 17.3714 11.2501 17.0356 11.2501 16.6214V10.9449C11.2501 10.5307 11.5859 10.1949 12.0001 10.1949C12.4143 10.1949 12.7501 10.5307 12.7501 10.9449V16.6214C12.7501 17.0356 12.4143 17.3714 12.0001 17.3714Z"
                                            fill="currentColor"
                                          />
                                        </svg>',
                        'path' => route('support.index')
                    ],
                ];
            @endphp

            @foreach ($menuItems as $item)
                <li>
                    <a href="{{ $item['path'] }}"
                        class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                        <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                            {!! $item['icon'] !!}
                        </span>
                        {{ $item['text'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <!-- Sign Out -->
        <form method="POST" action="{{ route('logout') }}" id="logout-form">
            @csrf
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                class="flex items-center w-full gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                @click="closeDropdown()">
                <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                        </path>
                    </svg>
                </span>
                Sign out
            </a>
        </form>
    </div>
    <!-- Dropdown End -->
</div>