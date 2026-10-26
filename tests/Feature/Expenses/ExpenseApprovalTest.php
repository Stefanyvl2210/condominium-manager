<?php

use App\Actions\Expenses\RecordExpense;
use App\Actions\Expenses\ReviewExpense;
use App\Actions\Expenses\UpdateExpense;
use App\Enums\ExpenseScope;
use App\Enums\ExpenseStatus;
use App\Exceptions\BillingException;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\ExpenseCategory;
use App\Models\User;

beforeEach(function () {
    condominium(['expense_approval_threshold' => 100_000]);
    $this->admin = User::factory()->admin()->create();
    $this->board = User::factory()->boardMember()->create();

    $this->record = fn (int $amount, array $data = []) => app(RecordExpense::class)->handle($this->admin, [
        'expense_category_id' => ExpenseCategory::factory()->create()->id,
        'scope' => ExpenseScope::Condominium,
        'description' => 'Water pump repair',
        'amount' => $amount,
        'incurred_on' => '2026-10-10',
        ...$data,
    ]);
});

it('approves small expenses automatically', function () {
    expect(($this->record)(99_999)->status)->toBe(ExpenseStatus::Approved);
});

it('sends expenses at or above the threshold to the board', function () {
    $expense = ($this->record)(100_000);

    expect($expense->status)->toBe(ExpenseStatus::PendingApproval)
        ->and($this->board->notifications()->count())->toBe(1);
});

it('drops scope targets that do not apply', function () {
    $building = Building::factory()->create();

    $expense = ($this->record)(5_000, ['building_id' => $building->id, 'unit_id' => 999]);

    expect($expense->building_id)->toBeNull()->and($expense->unit_id)->toBeNull();
});

it('lets the board approve or reject pending expenses', function () {
    $approved = app(ReviewExpense::class)->approve(($this->record)(200_000), $this->board);
    $rejected = app(ReviewExpense::class)->reject(($this->record)(300_000), $this->board, 'Get a second quote.');

    expect($approved->status)->toBe(ExpenseStatus::Approved)
        ->and($approved->reviewed_by)->toBe($this->board->id)
        ->and($rejected->status)->toBe(ExpenseStatus::Rejected)
        ->and($rejected->rejection_reason)->toBe('Get a second quote.');
});

it('only allows board members to review', function () {
    $expense = ($this->record)(200_000);

    expect($this->board->can('review', $expense))->toBeTrue()
        ->and($this->admin->can('review', $expense))->toBeFalse();
});

it('sends an approved expense back to the board when it grows past the threshold', function () {
    $expense = ($this->record)(50_000);

    app(UpdateExpense::class)->handle($expense, ['amount' => 150_000, 'scope' => ExpenseScope::Condominium]);

    expect($expense->fresh()->status)->toBe(ExpenseStatus::PendingApproval)
        ->and($this->board->notifications()->count())->toBe(1);
});

it('requires a new approval when a signed-off expense changes', function () {
    $expense = app(ReviewExpense::class)->approve(($this->record)(200_000), $this->board);

    app(UpdateExpense::class)->handle($expense, [
        'scope' => ExpenseScope::Condominium,
        'description' => 'A different repair',
    ]);

    expect($expense->fresh())
        ->status->toBe(ExpenseStatus::PendingApproval)
        ->reviewed_by->toBeNull()
        ->reviewed_at->toBeNull();
});

it('keeps the approval when only bookkeeping details change', function () {
    $expense = app(ReviewExpense::class)->approve(($this->record)(200_000), $this->board);

    app(UpdateExpense::class)->handle($expense, [
        'scope' => ExpenseScope::Condominium,
        'paid_on' => '2026-10-20',
        'payment_reference' => 'TRX-1',
    ]);

    expect($expense->fresh())
        ->status->toBe(ExpenseStatus::Approved)
        ->reviewed_by->toBe($this->board->id);
});

it('approves a pending expense once it drops below the threshold', function () {
    $expense = ($this->record)(200_000);

    app(UpdateExpense::class)->handle($expense, ['amount' => 80_000, 'scope' => ExpenseScope::Condominium]);

    expect($expense->fresh()->status)->toBe(ExpenseStatus::Approved);
});

it('refuses to change an expense that was already billed', function () {
    $expense = ($this->record)(5_000);
    $expense->update(['billing_period_id' => BillingPeriod::for(2026, 10)->id]);

    app(UpdateExpense::class)->handle($expense, ['amount' => 6_000, 'scope' => ExpenseScope::Condominium]);
})->throws(BillingException::class, 'can no longer change');
