<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Unit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Administration;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Account')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule('min:8')
                            ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->helperText(fn ($livewire) => $livewire instanceof CreateRecord ? null : 'Leave empty to keep the current password.'),
                    ]),
                Section::make('Role')
                    ->columnSpan(1)
                    ->schema([
                        Radio::make('role')
                            ->hiddenLabel()
                            ->options(UserRole::class)
                            ->default(UserRole::Owner)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('units.building'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->weight('medium')
                    ->description(fn (User $record) => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('units')
                    ->label('Units owned')
                    ->state(fn (User $record) => $record->units->map(fn (Unit $unit) => $unit->label)->sort()->values()->all())
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')->options(UserRole::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
