<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\ActivityReportMailService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendActivityReport extends Command
{
    protected $signature = 'activity:send-report {--force : Kirim sekarang tanpa menunggu jadwal}';

    protected $description = 'Kirim laporan aktivitas member ke atasan sesuai jadwal email';

    public function handle(ActivityReportMailService $service): int
    {
        $now = now();
        $enabled = filter_var(Setting::get('activity_report_enabled', false), FILTER_VALIDATE_BOOLEAN);

        if (! $enabled && ! $this->option('force')) {
            $this->info('Laporan aktivitas email tidak aktif.');

            return self::SUCCESS;
        }

        $frequency = (string) Setting::get('activity_report_frequency', 'weekly');
        $frequency = in_array($frequency, ['daily', 'weekly'], true) ? $frequency : 'weekly';
        $day = (int) Setting::get('activity_report_day', 5);
        $time = (string) Setting::get('activity_report_time', '18:00');

        if (! $this->option('force')) {
            $scheduled = Carbon::today()->setTimeFromTimeString($time);

            if ($frequency === 'weekly' && $now->isoWeekday() !== $day) {
                return self::SUCCESS;
            }

            if ($now->lt($scheduled)) {
                return self::SUCCESS;
            }

            $lastRun = Setting::get('activity_report_last_run');
            if ($lastRun && Carbon::parse($lastRun)->gte($scheduled)) {
                return self::SUCCESS;
            }
        }

        $sent = $service->send($frequency, $now);
        Setting::set('activity_report_last_run', $now->toDateTimeString());

        $this->info('Laporan aktivitas terkirim ke '.$sent.' atasan.');

        return self::SUCCESS;
    }
}
