<?php

namespace App\Filament\Resources\ExchangeRates\Pages;

use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use App\Filament\Widgets\ExchangeRateChart;
use Filament\Resources\Pages\ListRecords;

class ListExchangeRates extends ListRecords
{
    protected static string $resource = ExchangeRateResource::class;

    protected ?string $subheading = 'Billing is in US dollars. Payments in bolívares are converted with the rate in effect on the payment date.';

    protected function getHeaderWidgets(): array
    {
        return [
            ExchangeRateChart::class,
        ];
    }
}
