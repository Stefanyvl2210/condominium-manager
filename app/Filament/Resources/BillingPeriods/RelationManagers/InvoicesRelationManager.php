<?php

namespace App\Filament\Resources\BillingPeriods\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Statements';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return InvoicesTable::configure($table, showPeriod: false)
            ->defaultSort('number')
            ->recordActions([
                ViewAction::make()->url(fn (Invoice $record) => InvoiceResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
