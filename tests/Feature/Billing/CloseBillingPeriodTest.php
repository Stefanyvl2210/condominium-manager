<?php

use App\Actions\Billing\CloseBillingPeriod;
use App\Enums\BillingPeriodStatus;
use App\Enums\InvoiceLineType;
use App\Enums\InvoiceStatus;
use App\Exceptions\BillingException;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\InvoiceIssued;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    condominium(['reserve_fund_rate' => '10.00', 'payment_term_days' => 15]);

    $this->admin = User::factory()->admin()->create();
    $this->period = BillingPeriod::for(2026, 9);
    $this->issuedOn = Carbon::parse('2026-10-01');
    $this->close = fn () => app(CloseBillingPeriod::class)->handle($this->period, $this->admin, $this->issuedOn);
});

it('splits condominium expenses by ownership share and adds the reserve fund', function () {
    [$small, $large] = unitsWithShares(['40', '60']);
    Expense::factory()->create(['amount' => 100_000, 'incurred_on' => '2026-09-10']);

    ($this->close)();

    $smallInvoice = Invoice::whereBelongsTo($small)->sole();
    $largeInvoice = Invoice::whereBelongsTo($large)->sole();

    // 40 % of $1,000 = $400 plus 10 % reserve fund = $440.
    expect($smallInvoice->total)->toBe(44_000)
        ->and($largeInvoice->total)->toBe(66_000)
        ->and($smallInvoice->lines->firstWhere('type', InvoiceLineType::ReserveFund)->amount)->toBe(4_000)
        ->and($smallInvoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and($smallInvoice->due_on->toDateString())->toBe('2026-10-16')
        ->and($smallInvoice->number)->toBe('INV-202609-0001');
});

it('splits building expenses only among that building\'s units', function () {
    $towerA = Building::factory()->create(['code' => 'A']);
    $towerB = Building::factory()->create(['code' => 'B']);
    [$a1, $a2] = unitsWithShares(['25', '25'], $towerA);
    [$b1] = unitsWithShares(['50'], $towerB);

    Expense::factory()->forBuilding($towerA)->create(['amount' => 30_000, 'incurred_on' => '2026-09-05']);

    ($this->close)();

    expect(Invoice::whereBelongsTo($a1)->sole()->total)->toBe(16_500)
        ->and(Invoice::whereBelongsTo($a2)->sole()->total)->toBe(16_500)
        ->and(Invoice::whereBelongsTo($b1)->exists())->toBeFalse();
});

it('charges unit expenses in full without reserve fund', function () {
    [$unit, $other] = unitsWithShares(['50', '50']);
    Expense::factory()->forUnit($unit)->create(['amount' => 7_500, 'incurred_on' => '2026-09-20']);

    ($this->close)();

    $invoice = Invoice::whereBelongsTo($unit)->sole();

    expect($invoice->total)->toBe(7_500)
        ->and($invoice->lines)->toHaveCount(1)
        ->and($invoice->lines->first()->type)->toBe(InvoiceLineType::UnitCharge)
        ->and(Invoice::whereBelongsTo($other)->exists())->toBeFalse();
});

it('bills every cent of an expense that does not split evenly', function () {
    unitsWithShares(['33.333333', '33.333333', '33.333334']);
    Expense::factory()->create(['amount' => 10_000, 'incurred_on' => '2026-09-01']);

    ($this->close)();

    $billed = Invoice::query()->with('lines')->get()
        ->flatMap->lines
        ->where('type', InvoiceLineType::CondominiumExpense)
        ->sum('amount');

    expect($billed)->toBe(10_000);
});

it('only bills approved expenses incurred up to the end of the period', function () {
    unitsWithShares(['100']);
    $billable = Expense::factory()->create(['amount' => 10_000, 'incurred_on' => '2026-09-30']);
    $pending = Expense::factory()->pendingApproval()->create(['amount' => 500_000, 'incurred_on' => '2026-09-15']);
    $nextMonth = Expense::factory()->create(['amount' => 20_000, 'incurred_on' => '2026-10-01']);

    ($this->close)();

    expect(Invoice::sole()->total)->toBe(11_000)
        ->and($billable->fresh()->billing_period_id)->toBe($this->period->id)
        ->and($pending->fresh()->billing_period_id)->toBeNull()
        ->and($nextMonth->fresh()->billing_period_id)->toBeNull();
});

it('marks the period as closed with a snapshot of the settings', function () {
    unitsWithShares(['100']);
    Expense::factory()->create(['incurred_on' => '2026-09-01']);

    ($this->close)();

    $this->period->refresh();

    expect($this->period->status)->toBe(BillingPeriodStatus::Closed)
        ->and($this->period->closed_by)->toBe($this->admin->id)
        ->and($this->period->reserve_fund_rate)->toBe('10.00')
        ->and($this->period->issued_on->toDateString())->toBe('2026-10-01');
});

it('applies credit from advance payments to the new invoices', function () {
    [$unit] = unitsWithShares(['100']);
    Payment::factory()->for($unit)->approved()->usd(5_000)->create();
    Expense::factory()->create(['amount' => 10_000, 'incurred_on' => '2026-09-01']);

    ($this->close)();

    $invoice = Invoice::sole();

    expect($invoice->paid_amount)->toBe(5_000)
        ->and($invoice->status)->toBe(InvoiceStatus::PartiallyPaid);
});

it('notifies each owner', function () {
    [$unit] = unitsWithShares(['100']);
    Expense::factory()->create(['incurred_on' => '2026-09-01']);

    ($this->close)();

    Notification::assertSentTo($unit->owner, InvoiceIssued::class);
});

it('refuses to close a period twice', function () {
    unitsWithShares(['100']);

    ($this->close)();
    ($this->close)();
})->throws(BillingException::class, 'already closed');

it('refuses to close a period before it ends', function () {
    unitsWithShares(['100']);

    app(CloseBillingPeriod::class)->handle($this->period, $this->admin, Carbon::parse('2026-09-15'));
})->throws(BillingException::class, 'has not ended yet');

it('requires earlier periods to be closed first', function () {
    unitsWithShares(['100']);
    BillingPeriod::for(2026, 8);

    ($this->close)();
})->throws(BillingException::class, 'Close August 2026 first');

it('refuses to bill when ownership shares do not add up to 100 %', function () {
    unitsWithShares(['40', '50']);

    ($this->close)();
})->throws(BillingException::class, 'add up to 90.000000%');
