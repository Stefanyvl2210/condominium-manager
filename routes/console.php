<?php

use App\Actions\Billing\ApplyLateFees;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('billing:apply-late-fees', function (ApplyLateFees $applyLateFees) {
    $count = $applyLateFees->handle();

    $this->info("Late fees applied to {$count} overdue statements.");
})->purpose('Charge the late fee on statements that are past their due date');

Schedule::command('billing:apply-late-fees')->dailyAt('01:00');

// The public demo starts fresh every night. The command refuses to run unless APP_DEMO=true.
Schedule::command('demo:reset')->dailyAt('04:00')->when(fn () => config('app.demo'));
