<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * One-click sign-in for the seeded demo accounts. Only available when
 * APP_DEMO=true, so it can never be used on a site with real data.
 */
class DemoLoginController extends Controller
{
    /**
     * @return array<string, array{email: string, label: string, description: string, panel: string}>
     */
    public static function accounts(?string $panel = null): array
    {
        $accounts = [
            'admin' => [
                'email' => 'admin@example.com',
                'label' => 'Administrator',
                'description' => 'Manages units, expenses, billing and payments.',
                'panel' => 'admin',
            ],
            'board' => [
                'email' => 'board@example.com',
                'label' => 'Board member',
                'description' => 'Approves large expenses, read-only elsewhere.',
                'panel' => 'admin',
            ],
            'owner' => [
                'email' => 'owner@example.com',
                'label' => 'Owner',
                'description' => 'Sees statements and reports payments.',
                'panel' => 'portal',
            ],
        ];

        return $panel ? array_filter($accounts, fn (array $account) => $account['panel'] === $panel) : $accounts;
    }

    public function __invoke(string $account): RedirectResponse
    {
        abort_unless(config('app.demo'), 404);

        $details = self::accounts()[$account] ?? abort(404);
        $user = User::query()->where('email', $details['email'])->firstOrFail();

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->to(filament()->getPanel($details['panel'])->getUrl());
    }
}
