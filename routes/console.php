<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check git-sourced projects every minute; the command itself respects each
// project's configured auto_deploy_interval before triggering a re-deploy.
Schedule::command('app:auto-deploy-projects')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
