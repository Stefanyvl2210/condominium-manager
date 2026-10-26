<?php

use App\Actions\Payments\ReviewPayment;
use App\Actions\Payments\SubmitPayment;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\BillingException;
use App\Models\BillingPeriod;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\PaymentReviewed;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    condominium();

    $this->admin = User::factory()->admin()->create();
    [$this->unit] = unitsWithShares(['100']);
    $this->owner = $this->unit->owner;

    $this->older = Invoice::query()->create([
        'billing_period_id' => BillingPeriod::for(2026, 8)->id,
        'unit_id' => $this->unit->id,
        'number' => 'INV-202608-0001',
        'issued_on' => '2026-09-01',
        'due_on' => '2026-09-16',
        'total' => 10_000,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $this->newer = Invoice::query()->create([
        'billing_period_id' => BillingPeriod::for(2026, 9)->id,
        'unit_id' => $this->unit->id,
        'number' => 'INV-202609-0001',
        'issued_on' => '2026-10-01',
        'due_on' => '2026-10-16',
        'total' => 12_000,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $this->submit = fn (array $data) => app(SubmitPayment::class)->handle($this->owner, $this->unit, [
        'currency' => Currency::USD,
        'method' => PaymentMethod::Zelle,
        'reference' => 'ZL-123',
        'paid_on' => '2026-10-05',
        ...$data,
    ]);
});

it('converts bolívar payments with the rate in effect on the payment date', function () {
    ExchangeRate::factory()->create(['effective_on' => '2026-10-01', 'rate' => '40.0000']);
    ExchangeRate::factory()->create(['effective_on' => '2026-10-06', 'rate' => '45.0000']);

    $payment = ($this->submit)(['currency' => Currency::VES, 'amount' => 400_000, 'method' => PaymentMethod::MobilePayment]);

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->exchange_rate)->toBe('40.0000')
        ->and($payment->amount_usd)->toBe(10_000);
});

it('refuses bolívar payments when no exchange rate is known', function () {
    ($this->submit)(['currency' => Currency::VES, 'amount' => 400_000]);
})->throws(BillingException::class, 'no exchange rate for 2026-10-05');

it('does not touch balances until the payment is approved', function () {
    ($this->submit)(['amount' => 10_000]);

    expect($this->older->fresh()->paid_amount)->toBe(0);
});

it('applies an approved payment to the oldest invoice first', function () {
    $payment = ($this->submit)(['amount' => 15_000]);

    app(ReviewPayment::class)->approve($payment, $this->admin);

    $this->older->refresh();
    $this->newer->refresh();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Approved)
        ->and($this->older->status)->toBe(InvoiceStatus::Paid)
        ->and($this->newer->paid_amount)->toBe(5_000)
        ->and($this->newer->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($payment->allocations()->count())->toBe(2);

    Notification::assertSentTo($this->owner, PaymentReviewed::class);
});

it('keeps overpayments as credit for the unit', function () {
    $payment = ($this->submit)(['amount' => 30_000]);

    app(ReviewPayment::class)->approve($payment, $this->admin);

    expect($this->unit->outstandingBalance())->toBe(0)
        ->and($this->unit->availableCredit())->toBe(8_000);
});

it('records the reason when a payment is rejected', function () {
    $payment = ($this->submit)(['amount' => 10_000]);

    app(ReviewPayment::class)->reject($payment, $this->admin, 'Reference not found in the bank statement.');

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Rejected)
        ->rejection_reason->toBe('Reference not found in the bank statement.')
        ->and($this->older->fresh()->paid_amount)->toBe(0);
});

it('cannot review a payment twice', function () {
    $payment = ($this->submit)(['amount' => 10_000]);

    app(ReviewPayment::class)->approve($payment, $this->admin);
    app(ReviewPayment::class)->reject($payment, $this->admin, 'Too late');
})->throws(BillingException::class, 'already been reviewed');
