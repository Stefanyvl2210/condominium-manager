<?php

use App\Enums\BillingPeriodStatus;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\BillingPeriods\Pages\ListBillingPeriods;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\BillingPeriod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Notification::fake();
    condominium(['expense_approval_threshold' => 100_000]);
    Filament::setCurrentPanel('admin');

    $this->admin = User::factory()->admin()->create();
    $this->board = User::factory()->boardMember()->create();
});

it('closes a billing period from the table', function () {
    [$unit] = unitsWithShares(['100']);
    $period = BillingPeriod::for(now()->subMonthNoOverflow()->year, now()->subMonthNoOverflow()->month);
    Expense::factory()->create(['amount' => 20_000, 'incurred_on' => $period->startsOn()]);

    $this->actingAs($this->admin);

    livewire(ListBillingPeriods::class)
        ->callAction(TestAction::make('close')->table($period), data: ['issued_on' => now()->toDateString()])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($period->fresh()->status)->toBe(BillingPeriodStatus::Closed)
        ->and(Invoice::sole()->total)->toBe(22_000);
});

it('shows a friendly message when a period cannot be closed', function () {
    unitsWithShares(['60']);
    $period = BillingPeriod::for(now()->subMonthNoOverflow()->year, now()->subMonthNoOverflow()->month);

    $this->actingAs($this->admin);

    livewire(ListBillingPeriods::class)
        ->callAction(TestAction::make('close')->table($period), data: ['issued_on' => now()->toDateString()])
        ->assertNotified('The period could not be closed');

    expect($period->fresh()->isOpen())->toBeTrue();
});

it('records an expense that needs board approval', function () {
    $this->actingAs($this->admin);

    livewire(CreateExpense::class)
        ->fillForm([
            'description' => 'New water heater',
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'amount' => '1500.00',
            'incurred_on' => now()->toDateString(),
            'scope' => 'condominium',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Expense::sole())
        ->amount->toBe(150_000)
        ->status->toBe(ExpenseStatus::PendingApproval)
        ->created_by->toBe($this->admin->id);
});

it('only shows the expense approval actions to board members', function () {
    $expense = Expense::factory()->pendingApproval()->create();

    $this->actingAs($this->admin);
    livewire(ListExpenses::class)->assertActionHidden(TestAction::make('approve')->table($expense));

    $this->actingAs($this->board);
    livewire(ListExpenses::class)
        ->assertActionVisible(TestAction::make('approve')->table($expense))
        ->callAction(TestAction::make('approve')->table($expense));

    expect($expense->fresh()->status)->toBe(ExpenseStatus::Approved);
});

it('lets the administrator reject a payment with a reason', function () {
    [$unit] = unitsWithShares(['100']);
    $payment = Payment::factory()->for($unit)->create();

    $this->actingAs($this->admin);

    livewire(ListPayments::class)
        ->callAction(TestAction::make('reject')->table($payment), data: ['reason' => 'Reference not found.'])
        ->assertHasNoFormErrors();

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Rejected)
        ->rejection_reason->toBe('Reference not found.');
});

it('does not let board members review payments', function () {
    [$unit] = unitsWithShares(['100']);
    $payment = Payment::factory()->for($unit)->create();

    $this->actingAs($this->board);

    livewire(ListPayments::class)->assertActionHidden(TestAction::make('approve')->table($payment));
});

it('sends an edited expense back to the board when it crosses the threshold', function () {
    $expense = Expense::factory()->create(['amount' => 20_000]);

    $this->actingAs($this->admin);

    livewire(EditExpense::class, ['record' => $expense->getRouteKey()])
        ->fillForm(['amount' => '2000.00'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($expense->fresh())
        ->amount->toBe(200_000)
        ->status->toBe(ExpenseStatus::PendingApproval);
});
