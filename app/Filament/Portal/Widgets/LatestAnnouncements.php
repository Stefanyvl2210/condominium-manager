<?php

namespace App\Filament\Portal\Widgets;

use App\Models\Announcement;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class LatestAnnouncements extends Widget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.portal.widgets.latest-announcements';

    /**
     * @return Collection<int, Announcement>
     */
    public function getAnnouncements(): Collection
    {
        return Announcement::query()
            ->published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();
    }
}
