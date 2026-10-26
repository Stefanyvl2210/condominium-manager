<?php

namespace App\Filament\Pages;

use App\Filament\Forms\MoneyInput;
use App\Filament\NavigationGroup;
use App\Models\Condominium;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class CondominiumSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Administration;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Condominium settings';

    protected static ?string $slug = 'settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(Condominium::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(Condominium::current())
            ->columns(2)
            ->components([
                Section::make('Condominium')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('tax_id')
                            ->label('Tax ID')
                            ->maxLength(30),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->maxLength(255),
                    ]),
                Section::make('Billing rules')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reserve_fund_rate')
                            ->label('Reserve fund')
                            ->helperText('Added on top of each unit\'s share of common expenses.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->required(),
                        TextInput::make('late_fee_rate')
                            ->label('Late fee')
                            ->helperText('Charged once on the balance of overdue statements.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->required(),
                        TextInput::make('payment_term_days')
                            ->label('Payment term')
                            ->helperText('Days between issuing a statement and its due date.')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(90)
                            ->suffix('days')
                            ->required(),
                        MoneyInput::make('expense_approval_threshold')
                            ->label('Board approval threshold')
                            ->helperText('Expenses at or above this amount need a board member\'s approval.')
                            ->required(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Condominium::current()->update($this->form->getState());

        Notification::make()->title('Settings saved')->success()->send();
    }
}
