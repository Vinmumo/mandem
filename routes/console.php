<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\FplSyncService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fpl:sync', function (FplSyncService $sync) {
    $this->info(json_encode($sync->sync(), JSON_PRETTY_PRINT));
})->purpose('Import the Mandem league from Fantasy Premier League');

Schedule::command('fpl:sync')->everyTenMinutes()->withoutOverlapping()->between('10:00', '23:59');
