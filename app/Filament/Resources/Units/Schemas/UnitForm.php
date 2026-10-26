<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;
use App\Support\ShareAllocator;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Unit')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('building_id')
                            ->relationship('building', 'name')
                            ->required()
                            ->preload(),
                        TextInput::make('number')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('0302')
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, callable $get) => $rule
                                ->where('building_id', $get('building_id'))),
                        Select::make('type')
                            ->options(UnitType::class)
                            ->default(UnitType::Apartment)
                            ->required(),
                        TextInput::make('floor')
                            ->integer(),
                        TextInput::make('area')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('m²'),
                        TextInput::make('ownership_share')
                            ->label('Ownership share')
                            ->required()
                            ->numeric()
                            ->minValue(0.000001)
                            ->maxValue(100)
                            ->step(0.000001)
                            ->suffix('%')
                            ->helperText(fn (?Unit $record) => sprintf(
                                'Percentage of the whole condominium. Other units add up to %s %%.',
                                number_format(self::otherSharesTotal($record) / 1_000_000, 6),
                            ))
                            ->rule(fn (?Unit $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record) {
                                $total = self::otherSharesTotal($record) + (int) round(((float) $value) * 1_000_000);

                                if ($total > 100_000_000) {
                                    $fail(sprintf('Shares would add up to %s %%, more than 100 %%.', number_format($total / 1_000_000, 6)));
                                }
                            }),
                    ]),
                Section::make('Owner')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('owner_id')
                            ->label('Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required()->maxLength(255),
                                TextInput::make('email')->email()->required()->unique(User::class),
                                TextInput::make('phone')->tel(),
                            ])
                            ->createOptionUsing(fn (array $data): int => User::query()->create([
                                ...$data,
                                'role' => UserRole::Owner,
                                'password' => str()->password(),
                            ])->getKey())
                            ->helperText('Owners sign in to the portal to see statements and report payments.'),
                    ]),
            ]);
    }

    private static function otherSharesTotal(?Unit $record): int
    {
        return ShareAllocator::totalInMillionths(
            Unit::query()->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))->pluck('ownership_share'),
        );
    }
}
