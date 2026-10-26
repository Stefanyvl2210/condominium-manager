<?php

namespace App\Filament\Resources\BillingPeriods;

use App\Filament\NavigationGroup;
use App\Filament\Resources\BillingPeriods\Pages\ListBillingPeriods;
use App\Filament\Resources\BillingPeriods\Pages\ViewBillingPeriod;
use App\Filament\Resources\BillingPeriods\RelationManagers\ExpensesRelationManager;
use App\Filament\Resources\BillingPeriods\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\BillingPeriods\Schemas\BillingPeriodForm;
use App\Filament\Resources\BillingPeriods\Schemas\BillingPeriodInfolist;
use App\Filament\Resources\BillingPeriods\Tables\BillingPeriodsTable;
use App\Models\BillingPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BillingPeriodResource extends Resource
{
    protected static ?string $model = BillingPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Billing;

    protected static ?int $navigationSort = 1;

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof BillingPeriod ? $record->name : parent::getRecordTitle($record);
    }

    public static function form(Schema $schema): Schema
    {
        return BillingPeriodForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BillingPeriodInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingPeriodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
            ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingPeriods::route('/'),
            'view' => ViewBillingPeriod::route('/{record}'),
        ];
    }
}
