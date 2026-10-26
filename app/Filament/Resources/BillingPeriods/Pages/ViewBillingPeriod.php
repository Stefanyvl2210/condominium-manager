<?php

namespace App\Filament\Resources\BillingPeriods\Pages;

use App\Filament\Resources\BillingPeriods\BillingPeriodResource;
use App\Filament\Resources\BillingPeriods\CloseBillingPeriodAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBillingPeriod extends ViewRecord
{
    protected static string $resource = BillingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CloseBillingPeriodAction::make()->after(fn () => $this->refreshFormData(['status'])),
        ];
    }
}
