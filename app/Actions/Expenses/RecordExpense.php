<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseScope;
use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Condominium;
use App\Models\Expense;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Registers an expense. Expenses at or above the approval threshold need a
 * board member's sign-off before they can be billed to owners.
 */
class RecordExpense
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $recordedBy, array $data): Expense
    {
        $scope = $data['scope'] instanceof ExpenseScope ? $data['scope'] : ExpenseScope::from($data['scope']);
        $needsApproval = Condominium::current()->requiresApproval((int) $data['amount']);

        $expense = Expense::query()->create([
            ...$data,
            'building_id' => $scope === ExpenseScope::Building ? $data['building_id'] : null,
            'unit_id' => $scope === ExpenseScope::Unit ? $data['unit_id'] : null,
            'status' => $needsApproval ? ExpenseStatus::PendingApproval : ExpenseStatus::Approved,
            'created_by' => $recordedBy->id,
        ]);

        if ($needsApproval) {
            $this->notifyBoard($expense);
        }

        return $expense;
    }

    private function notifyBoard(Expense $expense): void
    {
        $board = User::query()->where('role', UserRole::BoardMember)->get();

        if ($board->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Expense awaiting approval')
            ->body(sprintf('%s for %s.', $expense->description, Money::format($expense->amount)))
            ->icon('heroicon-o-clock')
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->url(route('filament.admin.resources.expenses.view', $expense)),
            ])
            ->sendToDatabase($board);
    }
}
