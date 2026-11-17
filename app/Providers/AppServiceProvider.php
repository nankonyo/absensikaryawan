<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (! app()->runningUnitTests() && ($rootUrl = config('app.url'))) {
            \Illuminate\Support\Facades\URL::forceRootUrl($rootUrl);
        }

        try {
            $appName = \App\Models\Setting::get('app_name');

            if ($appName) {
                config(['app.name' => $appName]);
            }

            $timezone = \App\Models\Setting::get('timezone');

            if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Throwable $e) {
            // Settings table may not exist yet (fresh install / migration).
        }
    }
}
