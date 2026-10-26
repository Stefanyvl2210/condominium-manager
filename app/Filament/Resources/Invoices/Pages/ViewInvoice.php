<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\DownloadInvoiceAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @method Invoice getRecord()
 */
class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return "Statement {$this->getRecord()->number}";
    }

    protected function getHeaderActions(): array
    {
        return [
            DownloadInvoiceAction::make()->label('Download PDF'),
        ];
    }
}
