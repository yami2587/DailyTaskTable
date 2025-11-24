<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('dailylogs:generate')->dailyAt('09:00');
// Schedule::command('sheets:generate')->dailyAt('09:00');


//  php /path/to/project/artisan schedule:run >> /dev/null 2>&1


// To enable scheduler in server crontab:

// cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1


// Schedule::command('daily:sheet')->dailyAt('09:00');
