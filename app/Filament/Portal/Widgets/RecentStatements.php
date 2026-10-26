<?php

namespace App\Filament\Portal\Widgets;

use App\Filament\Portal\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\DownloadInvoiceAction;
use App\Models\Invoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentStatements extends TableWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 2];

    protected static ?string $heading = 'Recent statements';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Invoice::query()->forOwner(auth()->user())->with(['unit.building', 'billingPeriod'])->latest('issued_on')->limit(6))
            ->paginated(false)
            ->recordUrl(fn (Invoice $record) => InvoiceResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('billingPeriod.name')->label('Period'),
                TextColumn::make('unit.label')->label('Unit'),
                TextColumn::make('total')->money('USD', divideBy: 100),
                TextColumn::make('balance')
                    ->money('USD', divideBy: 100)
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([
                DownloadInvoiceAction::make()->iconButton(),
            ]);
    }
}
