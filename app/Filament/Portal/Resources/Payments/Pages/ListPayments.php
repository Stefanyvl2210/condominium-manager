<?php

namespace App\Filament\Portal\Resources\Payments\Pages;

use App\Filament\Portal\Resources\Payments\PaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected static ?string $title = 'My payments';

    protected ?string $subheading = 'Payments are applied to your balance once the administrator confirms them.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Report a payment'),
        ];
    }
}
