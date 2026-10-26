<?php

use App\Models\User;

it('is not available outside demo mode', function () {
    config(['app.demo' => false]);
    User::factory()->admin()->create(['email' => 'admin@example.com']);

    $this->post(route('demo.login', 'admin'))->assertNotFound();
    $this->assertGuest();
});

it('signs in a demo account and opens its panel', function (string $account, string $email, string $panel) {
    config(['app.demo' => true]);
    User::factory()->admin()->create(['email' => $email]);

    $this->post(route('demo.login', $account))->assertRedirect(url($panel));
    $this->assertAuthenticated();
})->with([
    ['admin', 'admin@example.com', '/admin'],
    ['owner', 'owner@example.com', '/portal'],
]);

it('rejects unknown demo accounts', function () {
    config(['app.demo' => true]);

    $this->post(route('demo.login', 'root'))->assertNotFound();
});

it('shows the landing page', function () {
    $this->get('/')->assertOk()->assertSee('Run a condominium without spreadsheets.');
});
