<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use App\Models\Unit;
use App\Support\ShareAllocator;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    public function getSubheading(): HtmlString
    {
        $total = ShareAllocator::totalInMillionths(Unit::query()->pluck('ownership_share'));
        $formatted = number_format($total / 1_000_000, 6);

        return ShareAllocator::isComplete($total)
            ? new HtmlString("Ownership shares add up to {$formatted} %.")
            : new HtmlString("<span style=\"color: var(--danger-600)\">Ownership shares add up to {$formatted} %. They must add up to exactly 100 % before a billing period can be closed.</span>");
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
