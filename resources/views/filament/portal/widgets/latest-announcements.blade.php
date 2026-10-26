<x-filament-widgets::widget>
    <x-filament::section heading="Notice board" icon="heroicon-o-megaphone">
        <div class="divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($this->getAnnouncements() as $announcement)
                <a href="{{ \App\Filament\Portal\Pages\NoticeBoard::getUrl() }}#announcement-{{ $announcement->id }}" class="block py-3 first:pt-0 last:pb-0">
                    <p class="flex items-center gap-1 text-sm font-medium text-gray-950 dark:text-white">
                        @if ($announcement->is_pinned)
                            <x-filament::icon icon="heroicon-s-star" class="h-4 w-4 text-warning-500" />
                        @endif
                        {{ $announcement->title }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $announcement->published_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No announcements yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
