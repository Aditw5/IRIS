<?php

namespace App\Console;

use App\Console\Commands\PengingatCron;
use App\Console\Commands\PengingatCronAsman;
use App\Console\Commands\PengingatRekalibrasiCron;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        PengingatCron::class,
        PengingatCronAsman::class,
        PengingatRekalibrasiCron::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        /*
     CARA RUNNING DILOKAL
     # php artisan schedule:work

     LIST SCHEDULER
     # php artisan schedule:list
    */

        $schedule->command('pengingat:cron')
            ->cron('20 7 * * *') // 08:20 setiap hari
            ->timezone(config('app.timezone'))
            ->withoutOverlapping()
            ->after(function () {
                $fp = fopen(storage_path('logs/laravel.log'), 'a');
                fwrite($fp, PHP_EOL . "SCHEDULER LARAVEL : " . date('Y-m-d H:i:s'));
                fclose($fp);
            });

        $schedule->command('pengingatasman:cron')
            ->cron('30 7 * * *') // 08:22 setiap hari
            ->timezone(config('app.timezone'))
            ->withoutOverlapping()
            ->after(function () {
                $fp = fopen(storage_path('logs/laravel.log'), 'a');
                fwrite($fp, PHP_EOL . "SCHEDULER LARAVEL : " . date('Y-m-d H:i:s'));
                fclose($fp);
            });

        $schedule->command('pengingat-rekalibrasi:cron')
            ->cron('40 7 * * *') // 07:40 setiap hari
            ->timezone(config('app.timezone'))
            ->withoutOverlapping()
            ->after(function () {
                $fp = fopen(storage_path('logs/laravel.log'), 'a');
                fwrite($fp, PHP_EOL . "SCHEDULER LARAVEL : " . date('Y-m-d H:i:s'));
                fclose($fp);
            });

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
