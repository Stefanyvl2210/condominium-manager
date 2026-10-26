<?php

namespace App\Filament\Portal\Resources\Payments\Pages;

use App\Actions\Payments\SubmitPayment;
use App\Exceptions\BillingException;
use App\Filament\Portal\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\Unit;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected static ?string $title = 'Report a payment';

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $unit = request()->integer('unit');

        if ($unit && auth()->user()->units()->whereKey($unit)->exists()) {
            $this->data['unit_id'] = $unit;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Payment
    {
        // The unit list is already limited to the owner's units; this guards against tampered requests.
        $unit = Unit::query()->ownedBy(auth()->user())->findOrFail($data['unit_id']);

        try {
            return app(SubmitPayment::class)->handle(auth()->user(), $unit, $data);
        } catch (BillingException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Payment reported. You will be notified once it is reviewed.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
