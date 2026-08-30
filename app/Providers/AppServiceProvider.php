<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useTailwind();
        \Carbon\Carbon::setLocale('id');

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $globalTz = \App\Models\Setting::where('school_id', 0)->where('setting_key', 'timezone')->value('setting_value');
                if ($globalTz) {
                    date_default_timezone_set($globalTz);
                    config(['app.timezone' => $globalTz]);
                }
            }
        } catch (\Throwable $e) {
            // DB not ready or during artisan commands
        }

        // Force HTTPS if accessed via secure proxy, explicitly secure, or if FORCE_HTTPS is true in .env
        // Removed app()->environment('production') so it doesn't force HTTPS on local LAN servers
        if (request()->header('x-forwarded-proto') === 'https' ||
            request()->isSecure() ||
            env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // Dynamic School Branding via View Composer
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            try {
                $schoolName = 'Sistem Absensi';
                $schoolLogo = 'logo.png';
                $settings = [];

                // Deteksi apakah akses via custom domain sekolah
                $requestHost    = request()->getHost();
                $globalHost     = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
                $isCustomDomain = ($requestHost !== $globalHost && $requestHost !== 'localhost');

                // Jika custom domain, cari school yang punya domain tersebut
                $schoolByDomain = null;
                if ($isCustomDomain) {
                    $schoolByDomain = \App\Models\School::where('domain', $requestHost)->first();
                    // Jika domain tidak cocok dengan sekolah manapun, anggap bukan custom domain
                    if (!$schoolByDomain) {
                        $isCustomDomain = false;
                    }
                }

                if (\Illuminate\Support\Facades\Auth::check()) {
                    $user = \Illuminate\Support\Facades\Auth::user();
                    if ($user->school_id) {
                        $settings = \App\Models\Setting::where('school_id', $user->school_id)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();

                        // Fallback to Global Settings (school_id = 0) if keys missing
                        $globalSettings = \App\Models\Setting::where('school_id', 0)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();

                        $settings = $settings + $globalSettings; // Union: School settings take precedence
                    } else {
                        // Super Admin or User without School - Use Global Settings
                        $schoolName = 'Super Admin Panel';
                        $settings = \App\Models\Setting::where('school_id', 0)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();
                    }
                } else {
                    $isSelfHosted = (config('app.mode') === 'self_hosted');
                    if ($isCustomDomain && $schoolByDomain) {
                        // Guest / Login Page via Custom Domain: load settings of that specific school
                        $settings = \App\Models\Setting::where('school_id', $schoolByDomain->id)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();
                        $globalSettings = \App\Models\Setting::where('school_id', 0)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();
                        $settings = $settings + $globalSettings;
                    } elseif ($isSelfHosted) {
                        // Guest / Login Page untuk Self-Hosted: gunakan sekolah pertama yang aktif
                        $firstSchool = \App\Models\School::where('is_active', true)->first();
                        $schoolId = $firstSchool ? $firstSchool->id : 0;
                        $settings = \App\Models\Setting::where('school_id', $schoolId)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();
                        $globalSettings = \App\Models\Setting::where('school_id', 0)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();
                        $settings = $settings + $globalSettings;
                    } else {
                        // Guest / Login Page untuk SaaS (Main Domain)
                        // Gunakan Global Settings (school_id = 0)
                        $settings = \App\Models\Setting::where('school_id', 0)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();
                    }
                }

                $schoolName = $settings['nama_sekolah'] ?? $schoolName;

                if (!empty($settings['timezone'])) {
                    date_default_timezone_set($settings['timezone']);
                    config(['app.timezone' => $settings['timezone']]);
                }

                // Logo: pakai logo sekolah jika akses via custom domain ATAU mode self-hosted.
                // Jika dari domain global (SaaS) → pakai logo SVG bawaan aplikasi (logo.svg)
                $isSelfHosted = (config('app.mode') === 'self_hosted');
                if ($isCustomDomain || $isSelfHosted) {
                    $schoolLogo = $settings['logo_filename'] ?? 'logo.svg';
                } else {
                    // null / string kosong → sidebar akan jatuh ke else branch (logo SVG bawaan)
                    $schoolLogo = null;
                }

                $view->with('school_name', $schoolName);
                $view->with('school_logo', $schoolLogo);
                $view->with('is_custom_domain', $isCustomDomain);
                $view->with('global_settings', $settings);

            } catch (\Exception $e) {
                $view->with('school_name', 'Sistem Absensi');
                $view->with('school_logo', 'logo.png');
                $view->with('is_custom_domain', false);
                $view->with('global_settings', []);
            }
        });
    }
}
