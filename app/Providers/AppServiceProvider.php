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
        \Illuminate\Pagination\Paginator::useBootstrap();

        if (config('app.env') !== 'local' || str_contains(request()->getHost(), 'smkassuniyah.sch.id')) {
            URL::forceScheme('https');
        }

        try {
            // Share common settings with all views
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::pluck('setting_value', 'setting_key')->toArray();

                \Illuminate\Support\Facades\View::share('school_name', $settings['nama_sekolah'] ?? 'SMK Assuniyah Tumijajar');
                \Illuminate\Support\Facades\View::share('school_logo', $settings['logo_filename'] ?? 'logo.png');
                \Illuminate\Support\Facades\View::share('global_settings', $settings);
            } else {
                // Fallback defaults
                \Illuminate\Support\Facades\View::share('school_name', 'SMK Assuniyah Tumijajar');
                \Illuminate\Support\Facades\View::share('school_logo', 'logo.png');
            }
        } catch (\Exception $e) {
            // Logs not available yet or DB connection failed
            \Illuminate\Support\Facades\View::share('school_name', 'SMK Assuniyah Tumijajar');
            \Illuminate\Support\Facades\View::share('school_logo', 'logo.png');
        }
    }
}
