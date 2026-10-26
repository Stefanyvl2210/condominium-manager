<?php

namespace App\Filament\Portal\Resources\Invoices;

use App\Filament\Portal\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Portal\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\DownloadInvoiceAction;
use App\Filament\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Models\Builders\InvoiceBuilder;
use App\Models\Invoice;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The signed-in owner's statements only.
 */
class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $modelLabel = 'statement';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getEloquentQuery(): InvoiceBuilder
    {
        return Invoice::query()->forOwner(auth()->user());
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['unit.building', 'billingPeriod']))
            ->defaultSort('issued_on', 'desc')
            ->columns([
                TextColumn::make('billingPeriod.name')
                    ->label('Period')
                    ->weight('medium')
                    ->description(fn (Invoice $record) => $record->number),
                TextColumn::make('unit.label')
                    ->label('Unit'),
                TextColumn::make('total')
                    ->money('USD', divideBy: 100),
                TextColumn::make('balance')
                    ->label('Balance due')
                    ->money('USD', divideBy: 100)
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('due_on')
                    ->date()
                    ->color(fn (Invoice $record) => $record->isOverdue() ? 'danger' : null)
                    ->description(fn (Invoice $record) => $record->isOverdue() ? 'Overdue' : null),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('unit')
                    ->relationship('unit', 'number', fn (Builder $query) => $query->where('owner_id', auth()->id()))
                    ->getOptionLabelFromRecordUsing(fn (Unit $record) => $record->label),
                Filter::make('outstanding')
                    ->label('Only unpaid')
                    ->query(fn (InvoiceBuilder $query) => $query->outstanding())
                    ->toggle(),
            ])
            ->recordActions([
                DownloadInvoiceAction::make()->iconButton(),
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }
}
