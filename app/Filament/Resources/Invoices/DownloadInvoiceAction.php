<?php

namespace App\Filament\Resources\Invoices;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class DownloadInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('pdf')
            ->label('PDF')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->url(fn (Invoice $record) => route('invoices.pdf', $record))
            ->openUrlInNewTab();
    }
}
