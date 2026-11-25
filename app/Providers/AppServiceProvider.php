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
        // Pada request HTTP, biarkan URL mengikuti origin yang dipakai (host,
        // skema, dan port) supaya form/asset selalu same-origin dan CSRF tidak
        // gagal saat aplikasi diakses lewat http maupun https.
        // APP_URL hanya dipakai sebagai root untuk CLI/queue (email, dsb).
        if (! app()->runningUnitTests() && app()->runningInConsole() && ($rootUrl = config('app.url'))) {
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

            $mailer = (string) \App\Models\Setting::get('mail_mailer', '');

            if (in_array($mailer, ['smtp', 'log', 'sendmail'], true)) {
                config(['mail.default' => $mailer]);
            }

            if ($mailer === 'smtp') {
                $host = (string) \App\Models\Setting::get('mail_host', '');
                $port = (int) \App\Models\Setting::get('mail_port', 0);
                $username = \App\Models\Setting::get('mail_username');
                $password = \App\Models\Setting::get('mail_password');
                $encryption = strtolower((string) \App\Models\Setting::get('mail_encryption', ''));

                config([
                    'mail.mailers.smtp.host' => $host !== '' ? $host : config('mail.mailers.smtp.host'),
                    'mail.mailers.smtp.port' => $port > 0 ? $port : config('mail.mailers.smtp.port'),
                    'mail.mailers.smtp.username' => $username !== null && $username !== '' ? $username : config('mail.mailers.smtp.username'),
                    'mail.mailers.smtp.password' => $password !== null && $password !== '' ? $password : config('mail.mailers.smtp.password'),
                    'mail.mailers.smtp.encryption' => $encryption !== '' && $encryption !== 'none' ? $encryption : null,
                    'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : null,
                ]);
            }

            $fromAddress = (string) \App\Models\Setting::get('mail_from_address', '');
            $fromName = (string) \App\Models\Setting::get('mail_from_name', '');

            if ($fromAddress !== '') {
                config(['mail.from.address' => $fromAddress]);
            }

            if ($fromName !== '') {
                config(['mail.from.name' => $fromName]);
            }
        } catch (\Throwable $e) {
            // Settings table may not exist yet (fresh install / migration).
        }
    }
}
