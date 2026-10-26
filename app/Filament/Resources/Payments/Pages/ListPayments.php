<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentStatus::Pending))
                ->badge(fn () => Payment::query()->where('status', PaymentStatus::Pending)->count() ?: null)
                ->badgeColor('warning'),
            'approved' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentStatus::Approved)),
            'rejected' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentStatus::Rejected)),
            'all' => Tab::make(),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return Payment::query()->where('status', PaymentStatus::Pending)->exists() ? 'pending' : 'all';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Record payment'),
        ];
    }
}
