<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Builders\InvoiceBuilder;
use App\Models\Invoice;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected ?string $subheading = 'Statements are issued when a billing period is closed and cannot be edited.';

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'outstanding' => Tab::make()
                ->modifyQueryUsing(fn (InvoiceBuilder $query) => $query->outstanding())
                ->badge(fn () => Invoice::query()->outstanding()->count()),
            'overdue' => Tab::make()
                ->modifyQueryUsing(fn (InvoiceBuilder $query) => $query->overdue())
                ->badge(fn () => Invoice::query()->overdue()->count())
                ->badgeColor('danger'),
            'paid' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InvoiceStatus::Paid)),
        ];
    }
}
