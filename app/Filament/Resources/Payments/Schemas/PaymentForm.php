<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Actions\Payments\SubmitPayment;
use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Exceptions\BillingException;
use App\Filament\Forms\MoneyInput;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

/**
 * Used by administrators in the back office and by owners in the portal.
 * Owners can only pick their own units.
 */
class PaymentForm
{
    public static function configure(Schema $schema, ?User $owner = null): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Payment')
                    ->columnSpan($owner ? 'full' : 2)
                    ->columns(2)
                    ->schema([
                        Select::make('unit_id')
                            ->label('Unit')
                            ->options(fn () => Unit::query()
                                ->with(['building', 'owner'])
                                ->when($owner, fn ($query) => $query->ownedBy($owner))
                                ->get()
                                ->sortBy('label')
                                ->mapWithKeys(fn (Unit $unit) => [$unit->id => $owner ? $unit->label : "{$unit->label} · {$unit->owner?->name}"]))
                            ->default(fn () => $owner?->units()->count() === 1 ? $owner->units()->value('id') : null)
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),
                        ToggleButtons::make('currency')
                            ->options([
                                Currency::USD->value => 'US dollars',
                                Currency::VES->value => 'Bolívares',
                            ])
                            ->default(Currency::USD->value)
                            ->inline()
                            ->required()
                            ->live(),
                        MoneyInput::make('amount')
                            ->prefix(fn (Get $get) => self::currency($get)->symbol())
                            ->required()
                            ->live(onBlur: true),
                        Select::make('method')
                            ->options(PaymentMethod::class)
                            ->required()
                            ->live(),
                        DatePicker::make('paid_on')
                            ->label('Payment date')
                            ->default(now())
                            ->maxDate(now())
                            ->required()
                            ->live(),
                        TextInput::make('reference')
                            ->label('Bank reference')
                            ->maxLength(100)
                            ->required(fn (Get $get) => self::method($get)?->requiresReference() ?? true),
                        TextEntry::make('usd_equivalent')
                            ->label('Credited in US dollars')
                            ->state(fn (Get $get) => self::usdEquivalent($get))
                            ->visible(fn (Get $get) => self::currency($get) === Currency::VES),
                        FileUpload::make('proof_path')
                            ->label('Proof of payment')
                            ->helperText('Screenshot or PDF of the transfer receipt.')
                            ->disk('uploads')
                            ->directory('payments')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->required(fn () => $owner !== null)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
                Section::make('Review')
                    ->columnSpan(1)
                    ->visible($owner === null)
                    ->schema([
                        Toggle::make('approve_now')
                            ->label('Approve immediately')
                            ->helperText('Use this when you have already confirmed the money, e.g. cash received at the office.')
                            ->default(false),
                    ]),
            ]);
    }

    public static function currency(Get $get): Currency
    {
        $currency = $get('currency');

        return $currency instanceof Currency ? $currency : (Currency::tryFrom((string) $currency) ?? Currency::USD);
    }

    public static function method(Get $get): ?PaymentMethod
    {
        $method = $get('method');

        return $method instanceof PaymentMethod ? $method : PaymentMethod::tryFrom((string) $method);
    }

    private static function usdEquivalent(Get $get): string
    {
        $amount = $get('amount');

        if (! is_numeric($amount)) {
            return 'Enter an amount';
        }

        try {
            [$rate, $usd] = app(SubmitPayment::class)->convert(
                Currency::VES,
                Money::toMinorUnits($amount),
                Carbon::parse($get('paid_on') ?? now()),
            );
        } catch (BillingException $exception) {
            return $exception->getMessage();
        }

        return Money::format($usd)." at Bs. {$rate} per USD";
    }
}
