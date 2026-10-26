<?php

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Portal\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Portal\Resources\Payments\Pages\CreatePayment;
use App\Models\BillingPeriod;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    condominium();
    Filament::setCurrentPanel('portal');

    [$this->mine, $this->theirs] = unitsWithShares(['50', '50']);
    $this->owner = $this->mine->owner;

    $period = BillingPeriod::for(2026, 9);
    $make = fn (Unit $unit) => Invoice::query()->create([
        'billing_period_id' => $period->id,
        'unit_id' => $unit->id,
        'number' => "INV-{$unit->id}",
        'issued_on' => '2026-10-01',
        'due_on' => '2026-10-16',
        'total' => 10_000,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $this->myInvoice = $make($this->mine);
    $this->theirInvoice = $make($this->theirs);

    $this->actingAs($this->owner);
});

it('only lists the owner\'s own statements', function () {
    livewire(ListInvoices::class)
        ->assertCanSeeTableRecords([$this->myInvoice])
        ->assertCanNotSeeTableRecords([$this->theirInvoice]);
});

it('hides other owners\' statements and PDFs', function () {
    $this->get("/portal/invoices/{$this->myInvoice->id}")->assertOk();
    $this->get("/portal/invoices/{$this->theirInvoice->id}")->assertNotFound();

    $this->get(route('invoices.pdf', $this->myInvoice))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('invoices.pdf', $this->theirInvoice))->assertForbidden();
});

it('lets an owner report a bolívar payment with proof', function () {
    Storage::fake('local');
    Notification::fake();
    ExchangeRate::factory()->create(['effective_on' => '2026-10-01', 'rate' => '50.0000']);
    User::factory()->admin()->create();

    livewire(CreatePayment::class)
        ->fillForm([
            'unit_id' => $this->mine->id,
            'currency' => Currency::VES->value,
            'amount' => '5000.00',
            'method' => PaymentMethod::MobilePayment->value,
            'paid_on' => '2026-10-05',
            'reference' => '0123456789',
            'proof_path' => UploadedFile::fake()->image('receipt.png'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $payment = Payment::sole();

    expect($payment)
        ->status->toBe(PaymentStatus::Pending)
        ->currency->toBe(Currency::VES)
        ->amount->toBe(500_000)
        ->amount_usd->toBe(10_000)
        ->submitted_by->toBe($this->owner->id)
        ->and($payment->proof_path)->not->toBeNull();

    Storage::disk('local')->assertExists($payment->proof_path);
});

it('requires proof of payment from owners', function () {
    livewire(CreatePayment::class)
        ->fillForm([
            'unit_id' => $this->mine->id,
            'currency' => Currency::USD->value,
            'amount' => '100',
            'method' => PaymentMethod::Zelle->value,
            'paid_on' => now()->toDateString(),
            'reference' => 'ZL-1',
        ])
        ->call('create')
        ->assertHasFormErrors(['proof_path' => 'required']);
});

it('rejects payments for units the owner does not own', function () {
    Storage::fake('local');

    livewire(CreatePayment::class)
        ->fillForm([
            'unit_id' => $this->theirs->id,
            'currency' => Currency::USD->value,
            'amount' => '100',
            'method' => PaymentMethod::Zelle->value,
            'paid_on' => now()->toDateString(),
            'reference' => 'ZL-1',
            'proof_path' => UploadedFile::fake()->image('receipt.png'),
        ])
        ->call('create')
        ->assertHasFormErrors(['unit_id']);

    expect(Payment::count())->toBe(0);
});
