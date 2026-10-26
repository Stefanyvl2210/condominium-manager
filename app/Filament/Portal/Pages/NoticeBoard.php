<?php

namespace App\Filament\Portal\Pages;

use App\Models\Announcement;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class NoticeBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Notice board';

    protected string $view = 'filament.portal.pages.notice-board';

    /**
     * @return Collection<int, Announcement>
     */
    public function getAnnouncements(): Collection
    {
        return Announcement::query()
            ->published()
            ->with('author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get();
    }
}
