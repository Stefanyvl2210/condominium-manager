@php
    use App\Http\Controllers\DemoLoginController;
    use App\Models\Condominium;

    $features = [
        ['Billing by ownership share', 'Close the month and every unit gets its statement: common expenses split by share, building expenses split within the tower, and the reserve fund on top.', 'M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5'],
        ['Dollars and bolívares', 'Statements are issued in USD. Owners pay in either currency and bolívares are converted with the official rate of the payment date.', 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Payments with proof', 'Owners report transfers with a screenshot. The administrator approves them and the money is applied to the oldest debt first.', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Board approval', 'Expenses above a threshold wait for a board member to sign off before they can be billed. The person who records it cannot approve it.', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
        ['Late fees and aging', 'A scheduled job charges the late fee on overdue statements. The aging report groups debt into 30, 60 and 90+ days.', 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['PDF statements and notices', 'Every statement downloads as a PDF and is emailed to the owner. A notice board keeps everyone informed.', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
    ];

    $steps = [
        ['Record expenses', 'Utilities, staff, maintenance and repairs, each charged to the whole condominium, one building or one unit.'],
        ['Board approves', 'Large expenses are reviewed by the board. Smaller ones are approved automatically.'],
        ['Close the month', 'One click issues a statement per unit, emails the owners and applies any credit they already had.'],
        ['Collect', 'Owners report payments from the portal; approved payments settle the oldest statements first.'],
    ];

    $condominium = Condominium::current();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Condominium management: billing by ownership share, dual-currency payments, board approvals and an owner portal.">
    <title>{{ $condominium->name }} · Condominium Manager</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <header class="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <img src="{{ asset('favicon.svg') }}" alt="" class="size-9">
            <span class="leading-tight">
                <span class="block text-sm font-semibold">{{ $condominium->name }}</span>
                <span class="block text-xs text-slate-500 dark:text-slate-400">Condominium Manager</span>
            </span>
        </a>
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ url('/portal') }}" class="rounded-lg px-3 py-2 font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Owner portal</a>
            <a href="{{ url('/admin') }}" class="rounded-lg bg-slate-900 px-3.5 py-2 font-medium text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">Back office</a>
        </nav>
    </header>

    <main>
        <section class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-10 pb-20 sm:px-6 lg:grid-cols-2 lg:pt-16">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                    Laravel 13 · Filament 5 · Pest
                </p>
                <h1 class="mt-5 text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                    Run a condominium without spreadsheets.
                </h1>
                <p class="mt-5 max-w-xl text-lg text-pretty text-slate-600 dark:text-slate-300">
                    Monthly statements split by ownership share, payments in dollars or bolívares, board approval for large expenses and a portal where owners see exactly what they owe.
                </p>

                @if (config('app.demo'))
                    <div class="mt-8">
                        <p class="text-sm font-medium text-slate-900 dark:text-white">Try the live demo. Pick a role:</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            @foreach (DemoLoginController::accounts() as $key => $account)
                                <form method="POST" action="{{ route('demo.login', $key) }}">
                                    @csrf
                                    <button type="submit" class="cursor-pointer group h-full w-full rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-indigo-400/50">
                                        <span class="block text-sm font-semibold">{{ $account['label'] }}</span>
                                        <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ $account['description'] }}</span>
                                        <span class="mt-3 block text-xs font-medium text-indigo-600 group-hover:underline dark:text-indigo-400">Sign in →</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">All people, units and amounts are fictional.</p>
                    </div>
                @else
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ url('/portal') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-500">Owner sign in</a>
                        <a href="{{ url('/admin') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50 dark:border-white/15 dark:hover:bg-white/5">Staff sign in</a>
                    </div>
                @endif
            </div>

            {{-- A sample statement, built with HTML so it stays crisp at any size. --}}
            <div class="relative">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-indigo-100 via-white to-emerald-100 blur-2xl dark:from-indigo-500/20 dark:via-transparent dark:to-emerald-500/10"></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">Monthly statement</p>
                            <p class="mt-1 font-semibold">Unit A-0302 · September</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">Partially paid</span>
                    </div>
                    <dl class="mt-5 divide-y divide-slate-100 text-sm dark:divide-white/10">
                        @foreach ([
                            ['24/7 security service', 'Everyone', '$26.03'],
                            ['Cleaning staff and supplies', 'Everyone', '$13.02'],
                            ['Elevator maintenance', 'Tower A', '$7.79'],
                            ['Common areas electricity', 'Everyone', '$8.40'],
                            ['Reserve fund (10 %)', '', '$5.52'],
                        ] as [$label, $scope, $amount])
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-600 dark:text-slate-300">
                                    {{ $label }}
                                    @if ($scope)
                                        <span class="ml-1.5 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-500 dark:bg-white/10 dark:text-slate-400">{{ $scope }}</span>
                                    @endif
                                </dt>
                                <dd class="font-medium tabular-nums">{{ $amount }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <div class="mt-4 rounded-xl bg-slate-50 p-4 dark:bg-white/5">
                        <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400"><span>Paid on Oct 3 · Bs. 2,197.64 at 54.94</span><span class="tabular-nums">−$40.00</span></div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="font-semibold">Balance due</span>
                            <span class="text-2xl font-semibold tabular-nums">$20.76</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-slate-50 py-20 dark:border-white/10 dark:bg-white/[0.02]">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Everything a building administrator does every month</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($features as [$title, $text, $icon])
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-slate-900">
                            <span class="inline-flex size-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                            </span>
                            <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">How a month is billed</h2>
            <ol class="mt-10 grid gap-6 md:grid-cols-4">
                @foreach ($steps as $index => [$title, $text])
                    <li class="relative rounded-2xl border border-slate-200 p-6 dark:border-white/10">
                        <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">0{{ $index + 1 }}</span>
                        <h3 class="mt-2 font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    </main>

    <footer class="border-t border-slate-200 dark:border-white/10">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:text-slate-400">
            <p>Condominium Manager</p>
            <p>Built with Laravel 13, Filament 5, Livewire and Tailwind CSS.</p>
        </div>
    </footer>
</body>
</html>
