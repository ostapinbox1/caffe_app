<?php

namespace App\Filament\Resources\RequestSections;

use App\Filament\Resources\RequestSections\Pages\ManageRequestSections;
use App\Models\RequestSection;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RequestSectionResource extends Resource
{
    protected static ?string $model = RequestSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Справочник';

    protected static ?string $navigationLabel = 'Разделы заявок';

    protected static ?string $modelLabel = 'раздел';

    protected static ?string $pluralModelLabel = 'разделы заявок';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(100),
                TextInput::make('sort')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label('Название'),
                TextColumn::make('categories_count')
                    ->label('Категорий')
                    ->counts('categories'),
                TextColumn::make('sort')
                    ->label('Порядок'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (RequestSection $record): bool => $record->categories()->exists() || $record->purchaseRequests()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRequestSections::route('/'),
        ];
    }
}
