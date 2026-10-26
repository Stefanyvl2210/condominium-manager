<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Actions\Expenses\UpdateExpense;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * @method Expense getRecord()
 */
class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    /**
     * @param  Expense  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Expense
    {
        return app(UpdateExpense::class)->handle($record, $data);
    }

    protected function getSavedNotificationTitle(): string
    {
        return $this->getRecord()->isPendingApproval()
            ? 'Expense saved and sent to the board for approval'
            : 'Expense saved';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
