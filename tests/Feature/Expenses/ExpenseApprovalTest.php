<?php

use App\Actions\Expenses\RecordExpense;
use App\Actions\Expenses\ReviewExpense;
use App\Enums\ExpenseScope;
use App\Enums\ExpenseStatus;
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
