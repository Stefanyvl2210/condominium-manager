<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Actions\Payments\ReviewPayment;
use App\Actions\Payments\SubmitPayment;
use App\Exceptions\BillingException;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\Unit;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected static ?string $title = 'Record payment';

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Payment
    {
        $approveNow = (bool) ($data['approve_now'] ?? false);
        unset($data['approve_now']);

        try {
            $payment = app(SubmitPayment::class)->handle(auth()->user(), Unit::findOrFail($data['unit_id']), $data);
        } catch (BillingException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            throw new Halt;
        }

        if ($approveNow) {
            app(ReviewPayment::class)->approve($payment, auth()->user());
        }

        return $payment;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
