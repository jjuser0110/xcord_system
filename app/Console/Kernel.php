<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected $commands = [
        //
	   'App\Console\Commands\DoClosings',
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
        $bankSettings = \App\Models\BankSetting::all();
        $today = \Carbon\Carbon::today();

        foreach ($bankSettings as $setting) {
                \App\Models\BankSnapshot::updateOrCreate(
                    [
                        'bank_setting_id' => $setting->id,
                        'snapshot_date' => $today,
                    ],
                    [
                        'capital' => $setting->capital,
                    ]
                );
            }
        })->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //$this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
