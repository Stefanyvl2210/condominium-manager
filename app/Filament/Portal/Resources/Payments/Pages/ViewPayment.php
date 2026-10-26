<?php

namespace App\Filament\Portal\Resources\Payments\Pages;

use App\Filament\Portal\Resources\Payments\PaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    public function getTitle(): string
    {
        return PaymentResource::getRecordTitle($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Withdraw'),
        ];
    }
}
