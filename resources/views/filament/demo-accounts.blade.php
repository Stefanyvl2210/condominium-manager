@if (config('app.demo'))
    <div class="mt-2 rounded-xl border border-dashed border-gray-300 p-4 dark:border-white/15">
        <p class="text-sm font-medium text-gray-950 dark:text-white">Demo accounts</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sign in with one click. Data is fictional and resets periodically.</p>

        <div class="mt-3 grid gap-2">
            @foreach (\App\Http\Controllers\DemoLoginController::accounts($panel) as $key => $account)
                <form method="POST" action="{{ route('demo.login', $key) }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-left text-sm ring-1 ring-gray-950/5 transition hover:bg-gray-100 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10">
                        <span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $account['label'] }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $account['description'] }}</span>
                        </span>
                        <span class="text-xs text-primary-600 dark:text-primary-400">Sign in →</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
@endif
