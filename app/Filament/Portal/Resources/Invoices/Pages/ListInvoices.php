<?php

namespace App\Filament\Portal\Resources\Invoices\Pages;

use App\Filament\Portal\Resources\Invoices\InvoiceResource;
use App\Filament\Portal\Resources\Payments\PaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'My statements';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pay')
                ->label('Report a payment')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url(PaymentResource::getUrl('create')),
        ];
    }
}
