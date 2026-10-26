<?php

use App\Actions\Billing\ApplyLateFees;
use App\Enums\InvoiceLineType;
use App\Enums\InvoiceStatus;
use App\Models\BillingPeriod;
use App\Models\Condominium;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

beforeEach(function () {
    condominium(['late_fee_rate' => '2.00']);
    [$this->unit] = unitsWithShares(['100']);
});

function invoiceFor($unit, array $attributes): Invoice
{
    return Invoice::query()->create([
        'billing_period_id' => BillingPeriod::for(2026, 9)->id,
        'unit_id' => $unit->id,
        'number' => 'INV-'.uniqid(),
        'issued_on' => '2026-10-01',
        'due_on' => '2026-10-16',
        'total' => 10_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
        ...$attributes,
    ]);
}

it('charges a percentage of the overdue balance once', function () {
    $invoice = invoiceFor($this->unit, ['total' => 10_000, 'paid_amount' => 4_000, 'status' => InvoiceStatus::PartiallyPaid]);

    $count = app(ApplyLateFees::class)->handle(Carbon::parse('2026-10-17'));
    app(ApplyLateFees::class)->handle(Carbon::parse('2026-10-20'));

    $invoice->refresh();

    // 2 % of the $60 still owed.
    expect($count)->toBe(1)
        ->and($invoice->total)->toBe(10_120)
        ->and($invoice->balance)->toBe(6_120)
        ->and($invoice->late_fee_applied_at)->not->toBeNull()
        ->and($invoice->lines->where('type', InvoiceLineType::LateFee))->toHaveCount(1);
});

it('leaves invoices that are paid or not yet due alone', function () {
    $paid = invoiceFor($this->unit, ['paid_amount' => 10_000, 'status' => InvoiceStatus::Paid]);
    $notDue = invoiceFor($this->unit, ['due_on' => '2026-10-31', 'billing_period_id' => BillingPeriod::for(2026, 10)->id]);

    $count = app(ApplyLateFees::class)->handle(Carbon::parse('2026-10-17'));

    expect($count)->toBe(0)
        ->and($paid->fresh()->total)->toBe(10_000)
        ->and($notDue->fresh()->total)->toBe(10_000);
});

it('does nothing when late fees are disabled', function () {
    Condominium::current()->update(['late_fee_rate' => '0']);
    invoiceFor($this->unit, []);

    expect(app(ApplyLateFees::class)->handle(Carbon::parse('2026-11-01')))->toBe(0);
});
