<?php

use App\Actions\Billing\ApplyLateFees;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('billing:apply-late-fees', function (ApplyLateFees $applyLateFees) {
    $count = $applyLateFees->handle();

    $this->info("Late fees applied to {$count} overdue statements.");
})->purpose('Charge the late fee on statements that are past their due date');

Schedule::command('billing:apply-late-fees')->dailyAt('01:00');
