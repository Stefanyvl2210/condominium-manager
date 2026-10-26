<?php

namespace App\Filament\Resources\BillingPeriods\Pages;

use App\Enums\BillingPeriodStatus;
use App\Filament\Resources\BillingPeriods\BillingPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingPeriods extends ListRecords
{
    protected static string $resource = BillingPeriodResource::class;

    protected ?string $subheading = 'Open a period for each month, then close it to issue one statement per unit.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Open period')
                ->modalWidth('md')
                ->mutateDataUsing(fn (array $data) => [...$data, 'status' => BillingPeriodStatus::Open]),
        ];
    }
}
