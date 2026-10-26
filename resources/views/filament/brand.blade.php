<div class="flex items-center gap-2">
    <img src="{{ asset('favicon.svg') }}" alt="" class="h-8 w-8">
    <div class="leading-tight">
        <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ \App\Models\Condominium::current()->name }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $subtitle ?? 'Back office' }}</div>
    </div>
</div>
