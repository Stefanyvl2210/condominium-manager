<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\PaymentReviewActions;
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
            PaymentReviewActions::approve(),
            PaymentReviewActions::reject(),
        ];
    }
}
