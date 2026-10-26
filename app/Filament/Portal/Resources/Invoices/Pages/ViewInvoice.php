<?php

namespace App\Filament\Portal\Resources\Invoices\Pages;

use App\Filament\Portal\Resources\Invoices\InvoiceResource;
use App\Filament\Portal\Resources\Payments\PaymentResource;
use App\Filament\Resources\Invoices\DownloadInvoiceAction;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @method Invoice getRecord()
 */
class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return "{$this->getRecord()->billingPeriod->name} statement";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pay')
                ->label('Report a payment')
                ->icon(Heroicon::OutlinedBanknotes)
                ->visible(fn () => $this->getRecord()->balance > 0)
                ->url(fn () => PaymentResource::getUrl('create', ['unit' => $this->getRecord()->unit_id])),
            DownloadInvoiceAction::make()->label('Download PDF'),
        ];
    }
}
