<?php

namespace App\Filament\Resources\ChecklistTemplates;

use App\Enums\ChecklistDeadline;
use App\Filament\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use App\Filament\Resources\ChecklistTemplates\Pages\EditChecklistTemplate;
use App\Filament\Resources\ChecklistTemplates\Pages\ListChecklistTemplates;
use App\Models\ChecklistTemplate;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Конструктор чек-листов: разделы → пункты, кому выдаётся и к какому сроку сдать.
 */
class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = ChecklistTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Чек-листы';

    protected static ?string $modelLabel = 'чек-лист';

    protected static ?string $pluralModelLabel = 'чек-листы';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Чек-лист')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->placeholder('Закрытие смены бариста')
                            ->required()
                            ->maxLength(150)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Пояснение для сотрудника')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        ToggleButtons::make('deadline_type')
                            ->label('Срок сдачи')
                            ->options(ChecklistDeadline::class)
                            ->default(ChecklistDeadline::ShiftEnd->value)
                            ->inline()
                            ->live()
                            ->required(),
                        TimePicker::make('due_time')
                            ->label('Сдать до')
                            ->seconds(false)
                            ->helperText('Если смена началась позже этого времени — даётся час от начала смены.')
                            ->visible(fn (Get $get): bool => self::deadline($get) === ChecklistDeadline::Time)
                            ->required(fn (Get $get): bool => self::deadline($get) === ChecklistDeadline::Time),
                        Toggle::make('is_active')
                            ->label('Выдавать на смене')
                            ->helperText('Выключите, чтобы временно не выдавать, не удаляя.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
                Section::make('Кому выдавать')
                    ->description('Чек-лист появляется у сотрудника, когда он нажимает «Начать смену».')
                    ->columns(2)
                    ->schema([
                        Select::make('positions')
                            ->label('Должностям')
                            ->relationship('positions', 'name')
                            ->multiple()
                            ->preload(),
                        Select::make('users')
                            ->label('Отдельным сотрудникам')
                            ->relationship('users', 'name', fn (Builder $query) => $query
                                ->where('cafe_id', Auth::user()?->cafe_id)
                                ->where('is_active', true)
                                ->orderBy('name'))
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->fullName())
                            ->multiple()
                            ->preload(),
                    ]),
                Section::make('Пункты')
                    ->schema([
                        Repeater::make('sections')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Добавить раздел')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Раздел')
                                    ->placeholder('Оборудование')
                                    ->required()
                                    ->maxLength(150),
                                Repeater::make('items')
                                    ->label('Пункты раздела')
                                    ->relationship()
                                    ->orderColumn('sort')
                                    ->addActionLabel('Добавить пункт')
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('text')
                                            ->label('Что сделать')
                                            ->placeholder('Кофемашина выключена')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('hint')
                                            ->label('Подсказка (необязательно)')
                                            ->placeholder('Холодильник не отключать')
                                            ->maxLength(255),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('deadline')
                    ->label('Срок')
                    ->state(fn (ChecklistTemplate $record): string => $record->deadlineLabel()),
                TextColumn::make('positions.name')
                    ->label('Должности')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('users_count')
                    ->label('Лично')
                    ->counts('users'),
                TextColumn::make('items_count')
                    ->label('Пунктов')
                    ->counts('items'),
                ToggleColumn::make('is_active')
                    ->label('Выдавать'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private static function deadline(Get $get): ?ChecklistDeadline
    {
        $value = $get('deadline_type');

        return $value instanceof ChecklistDeadline ? $value : ChecklistDeadline::tryFrom((string) $value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChecklistTemplates::route('/'),
            'create' => CreateChecklistTemplate::route('/create'),
            'edit' => EditChecklistTemplate::route('/{record}/edit'),
        ];
    }
}
