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

        // Only force HTTPS on production domain
        if (str_contains(request()->getHost(), 'smkassuniyah.sch.id')) {
            URL::forceScheme('https');
        }

        // Dynamic School Branding via View Composer
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            try {
                $schoolName = 'Sistem Absensi';
                $schoolLogo = 'logo.png';
                $settings = [];

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

                    // Guest / Login Page
                    // 1. Try Global Settings (school_id = 0)
                    $settings = \App\Models\Setting::where('school_id', 0)
                        ->pluck('setting_value', 'setting_key')
                        ->toArray();

                    // 2. Fallback to Default School (ID 3) if Global empty (or strictly empty logo)
                    if (empty($settings['logo_filename'])) {
                        $fallbackSettings = \App\Models\Setting::where('school_id', 3)
                            ->pluck('setting_value', 'setting_key')
                            ->toArray();
                        $settings = array_merge($fallbackSettings, $settings); // Global overrides fallback, but we want fallback if missing
                        // Actually array_merge key overwrites.
                        // $settings = $fallbackSettings + $settings; // Union, left preserved.
                        // Let's just use simple logic.
                        if (empty($settings)) {
                            $settings = $fallbackSettings;
                        }
                    }
                }

                $schoolName = $settings['nama_sekolah'] ?? $schoolName;
                $schoolLogo = $settings['logo_filename'] ?? $schoolLogo;

                $view->with('school_name', $schoolName);
                $view->with('school_logo', $schoolLogo);
                $view->with('global_settings', $settings);

            } catch (\Exception $e) {
                $view->with('school_name', 'Sistem Absensi');
                $view->with('school_logo', 'logo.png');
                $view->with('global_settings', []);
            }
        });
    }
}
