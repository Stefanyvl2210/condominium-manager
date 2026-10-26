<?php

use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('refuses to wipe the database outside demo mode', function () {
    config(['app.demo' => false]);
    $user = User::factory()->create();

    $this->artisan('demo:reset')
        ->expectsOutputToContain('Refusing to wipe the database')
        ->assertFailed();

    expect($user->fresh())->not->toBeNull();
});

it('is scheduled nightly only when demo mode is on', function (bool $demo, bool $due) {
    config(['app.demo' => $demo]);

    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains($event->command ?? '', 'demo:reset'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 4 * * *')
        ->and($event->filtersPass(app()))->toBe($due);
})->with([
    'demo' => [true, true],
    'real data' => [false, false],
]);
