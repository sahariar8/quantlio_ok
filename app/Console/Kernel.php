<?php

namespace App\Console;

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
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('call:queueOrderCodeFinal')->everyMinute()->withoutOverlapping(); // ->runInBackground()->withoutOverlapping();
       // $schedule->command('call:generatePDFReportsFinal2')->everyMinute()->withoutOverlapping(); 
        
        
        // $schedule->command('call:generatePDF')->everyMinute()->withoutOverlapping(); 
        //$schedule->command('call:assignOrderCodeToProcess2')->everyMinute()->withoutOverlapping(); // ->runInBackground()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
