<?php

use App\Models\Unit;
use App\Models\User;

beforeEach(fn () => condominium());

it('lets administrators and board members into the back office only', function (string $state, int $admin, int $portal) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get('/admin')->assertStatus($admin);
    $this->actingAs($user)->get('/portal')->assertStatus($portal);
})->with([
    'administrator' => ['admin', 200, 403],
    'board member without units' => ['boardMember', 200, 403],
]);

it('lets owners into the portal only', function () {
    $owner = Unit::factory()->create()->owner;

    $this->actingAs($owner)->get('/portal')->assertOk();
    $this->actingAs($owner)->get('/admin')->assertForbidden();
});

it('lets a board member who owns a unit use both panels', function () {
    $board = User::factory()->boardMember()->create();
    Unit::factory()->for($board, 'owner')->create();

    $this->actingAs($board)->get('/admin')->assertOk();
    $this->actingAs($board)->get('/portal')->assertOk();
});

it('renders every back-office page for an administrator', function (string $url) {
    $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
})->with([
    '/admin',
    '/admin/billing-periods',
    '/admin/invoices',
    '/admin/payments',
    '/admin/payments/create',
    '/admin/exchange-rates',
    '/admin/delinquency',
    '/admin/expenses',
    '/admin/expenses/create',
    '/admin/vendors',
    '/admin/vendors/create',
    '/admin/expense-categories',
    '/admin/buildings',
    '/admin/buildings/create',
    '/admin/units',
    '/admin/units/create',
    '/admin/announcements',
    '/admin/announcements/create',
    '/admin/users',
    '/admin/users/create',
    '/admin/settings',
]);

it('keeps board members read-only', function (string $url) {
    $this->actingAs(User::factory()->boardMember()->create())->get($url)->assertForbidden();
})->with([
    '/admin/expenses/create',
    '/admin/units/create',
    '/admin/payments/create',
    '/admin/users/create',
    '/admin/settings',
]);

it('renders every portal page for an owner', function (string $url) {
    $this->actingAs(Unit::factory()->create()->owner)->get($url)->assertOk();
})->with([
    '/portal',
    '/portal/invoices',
    '/portal/payments',
    '/portal/payments/create',
    '/portal/notice-board',
]);
