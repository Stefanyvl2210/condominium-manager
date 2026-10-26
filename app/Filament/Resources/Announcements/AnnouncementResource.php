<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Community;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('body')
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3', 'bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ]),
                    ]),
                Section::make('Publishing')
                    ->columnSpan(1)
                    ->schema([
                        DateTimePicker::make('published_at')
                            ->label('Publish at')
                            ->default(now())
                            ->helperText('Leave empty to keep it as a draft.'),
                        Toggle::make('is_pinned')
                            ->label('Pin to the top'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('')
                    ->icon(fn (bool $state) => $state ? Heroicon::Star : null)
                    ->color('warning'),
                TextColumn::make('title')
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('author.name')
                    ->placeholder('-'),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->placeholder('Draft')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
