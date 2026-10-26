<?php

namespace App\Actions\Payments;

use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\BillingException;
use App\Models\ExchangeRate;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Records a payment reported by an owner (or by the administrator on their
 * behalf). Bolívar payments are converted to USD with the rate in effect on
 * the payment date, so the owner sees exactly how much of their debt it covers.
 */
class SubmitPayment
{
    /**
     * @param  array{currency: Currency|string, amount: int, method: PaymentMethod|string, paid_on: string|\DateTimeInterface, reference?: ?string, proof_path?: ?string, notes?: ?string}  $data
     */
    public function handle(User $submittedBy, Unit $unit, array $data): Payment
    {
        $currency = $data['currency'] instanceof Currency ? $data['currency'] : Currency::from($data['currency']);
        $paidOn = Carbon::parse($data['paid_on'])->startOfDay();

        [$rate, $amountUsd] = $this->convert($currency, $data['amount'], $paidOn);

        $payment = Payment::query()->create([
            'unit_id' => $unit->id,
            'submitted_by' => $submittedBy->id,
            'currency' => $currency,
            'amount' => $data['amount'],
            'exchange_rate' => $rate,
            'amount_usd' => $amountUsd,
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'paid_on' => $paidOn,
            'proof_path' => $data['proof_path'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => PaymentStatus::Pending,
        ]);

        $this->notifyAdministrators($payment);

        return $payment;
    }

    /**
     * @return array{0: ?string, 1: int}
     */
    public function convert(Currency $currency, int $amount, \DateTimeInterface $paidOn): array
    {
        if ($currency === Currency::USD) {
            return [null, $amount];
        }

        $rate = ExchangeRate::effectiveOn($paidOn)
            ?? throw BillingException::missingExchangeRate($paidOn->format('Y-m-d'));

        return [$rate->rate, Money::vesToUsd($amount, $rate->rate)];
    }

    private function notifyAdministrators(Payment $payment): void
    {
        $admins = User::query()->where('role', UserRole::Admin)->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('New payment to review')
            ->body(sprintf(
                '%s reported %s for unit %s.',
                $payment->submitter->name,
                Money::format($payment->amount, $payment->currency),
                $payment->unit->label,
            ))
            ->icon('heroicon-o-banknotes')
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->url(route('filament.admin.resources.payments.view', $payment), shouldOpenInNewTab: false),
            ])
            ->sendToDatabase($admins);
    }
}
