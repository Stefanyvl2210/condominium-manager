<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Enums\ExpenseScope;
use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $scope = $data['scope'] instanceof ExpenseScope ? $data['scope'] : ExpenseScope::from($data['scope']);

        return [
            ...$data,
            'building_id' => $scope === ExpenseScope::Building ? $data['building_id'] : null,
            'unit_id' => $scope === ExpenseScope::Unit ? $data['unit_id'] : null,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
