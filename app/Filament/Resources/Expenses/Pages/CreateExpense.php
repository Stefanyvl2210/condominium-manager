<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Actions\Expenses\RecordExpense;
use App\Enums\ExpenseStatus;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Resources\Pages\CreateRecord;

/**
 * @method Expense getRecord()
 */
class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Expense
    {
        return app(RecordExpense::class)->handle(auth()->user(), $data);
    }

    protected function getCreatedNotificationTitle(): string
    {
        return $this->getRecord()->status === ExpenseStatus::PendingApproval
            ? 'Expense recorded and sent to the board for approval'
            : 'Expense recorded';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
