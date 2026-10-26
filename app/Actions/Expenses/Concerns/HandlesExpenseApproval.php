<?php

namespace App\Actions\Expenses\Concerns;

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
 * Shared rules for recording and editing expenses: who pays, and whether the
 * board has to sign off before the expense can be billed.
 */
trait HandlesExpenseApproval
{
    /**
     * Keep only the target that matches the scope (a building or a unit).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withScopeTargets(array $data): array
    {
        $scope = $data['scope'] instanceof ExpenseScope ? $data['scope'] : ExpenseScope::from($data['scope']);

        return [
            ...$data,
            'building_id' => $scope === ExpenseScope::Building ? $data['building_id'] : null,
            'unit_id' => $scope === ExpenseScope::Unit ? $data['unit_id'] : null,
        ];
    }

    protected function statusFor(int $amount): ExpenseStatus
    {
        return Condominium::current()->requiresApproval($amount)
            ? ExpenseStatus::PendingApproval
            : ExpenseStatus::Approved;
    }

    protected function notifyBoard(Expense $expense): void
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
