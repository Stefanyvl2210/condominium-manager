<?php

namespace App\Filament\Portal\Resources\Payments;

use App\Filament\Portal\Resources\Payments\Pages\CreatePayment;
use App\Filament\Portal\Resources\Payments\Pages\ListPayments;
use App\Filament\Portal\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Payments for the signed-in owner's units.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'My payments';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Payment ? "Payment from {$record->paid_on->toFormattedDateString()}" : parent::getRecordTitle($record);
    }

    /**
     * @return Builder<Payment>
     */
    public static function getEloquentQuery(): Builder
    {
        return Payment::query()->forOwner(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema, owner: auth()->user());
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table, forOwner: true)
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()->label('Withdraw'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
        ];
    }
}
