<x-filament-panels::page>
    <div class="grid gap-4">
        @forelse ($this->getAnnouncements() as $announcement)
            <x-filament::section id="announcement-{{ $announcement->id }}">
                <x-slot name="heading">
                    <span class="flex items-center gap-2">
                        @if ($announcement->is_pinned)
                            <x-filament::badge color="warning" icon="heroicon-s-star">Pinned</x-filament::badge>
                        @endif
                        {{ $announcement->title }}
                    </span>
                </x-slot>
                <x-slot name="description">
                    {{ $announcement->published_at->toFormattedDateString() }}
                    @if ($announcement->author) · {{ $announcement->author->name }} @endif
                </x-slot>

                <div class="fi-prose">
                    {!! str($announcement->body)->sanitizeHtml() !!}
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">There are no announcements yet.</p>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
