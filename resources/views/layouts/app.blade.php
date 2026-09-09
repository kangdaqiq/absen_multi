<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | Sistem Absensi by Jagat Tech</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo/logo-icon.ico') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' :
                        'light';
                    this.theme = savedTheme || systemTheme;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.theme === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,
                isFullScreen: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                },

                setFullScreen(val) {
                    this.isFullScreen = !!val;
                    if (this.isFullScreen) {
                        document.documentElement.classList.add('is-fullscreen');
                        document.body.classList.add('is-fullscreen');
                    } else {
                        document.documentElement.classList.remove('is-fullscreen');
                        document.body.classList.remove('is-fullscreen');
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            document.addEventListener('DOMContentLoaded', () => {
                if (theme === 'dark') {
                    document.body.classList.add('dark', 'bg-gray-900');
                } else {
                    document.body.classList.remove('dark', 'bg-gray-900');
                }
            });
        })();

        // Fullscreen listener for global kiosk mode
        ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
            document.addEventListener(evt, () => {
                const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
                if (window.Alpine && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').setFullScreen(isFs);
                } else {
                    if (isFs) {
                        document.documentElement.classList.add('is-fullscreen');
                        document.body.classList.add('is-fullscreen');
                    } else {
                        document.documentElement.classList.remove('is-fullscreen');
                        document.body.classList.remove('is-fullscreen');
                    }
                }
            });
        });
    </script>

    <!-- Fullscreen Mode Global Styles (Hides Sidebar & Top Header) -->
    <style>
        :fullscreen aside#sidebar,
        :-webkit-full-screen aside#sidebar,
        :-moz-full-screen aside#sidebar,
        :-ms-fullscreen aside#sidebar,
        body.is-fullscreen aside#sidebar,
        html.is-fullscreen aside#sidebar,
        body.is-fullscreen #backdrop {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
        }

        :fullscreen header,
        :-webkit-full-screen header,
        :-moz-full-screen header,
        :-ms-fullscreen header,
        body.is-fullscreen header,
        html.is-fullscreen header {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            pointer-events: none !important;
        }

        :fullscreen .flex-1,
        :-webkit-full-screen .flex-1,
        :-moz-full-screen .flex-1,
        :-ms-fullscreen .flex-1,
        body.is-fullscreen .flex-1,
        html.is-fullscreen .flex-1 {
            margin-left: 0 !important;
            padding-left: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        :fullscreen .max-w-\(--breakpoint-2xl\),
        :-webkit-full-screen .max-w-\(--breakpoint-2xl\),
        body.is-fullscreen .max-w-\(--breakpoint-2xl\),
        html.is-fullscreen .max-w-\(--breakpoint-2xl\) {
            max-width: 100% !important;
        }
    </style>
    
    @stack('styles')
</head>

<body
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                '!ml-0': $store.sidebar.isFullScreen,
                'xl:ml-[290px]': !$store.sidebar.isFullScreen && ($store.sidebar.isExpanded || $store.sidebar.isHovered),
                'xl:ml-[90px]': !$store.sidebar.isFullScreen && (!$store.sidebar.isExpanded && !$store.sidebar.isHovered),
                'ml-0': $store.sidebar.isMobileOpen
            }"
            :style="$store.sidebar.isFullScreen ? 'margin-left: 0px !important;' : ''">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6" :class="{ '!max-w-none !w-full': $store.sidebar.isFullScreen }">
                @yield('content')
            </div>
        </div>

    </div>

    <!-- Client-side Search Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInputs = document.querySelectorAll('.client-search');
            searchInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const table = document.querySelector('table');
                    if (!table) return;
                    
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        // Skip empty state rows
                        if (row.querySelector('td[colspan]')) return;
                        
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>

</body>

@stack('scripts')

</html>
