<?php

namespace App\Filament\Resources\Cafes;

use App\Filament\Resources\Cafes\Pages\ManageCafes;
use App\Models\Cafe;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Название и адрес своей кофейни. Сотрудник привязан к кофейне через учётную запись;
 * когда появится вторая, здесь будет список и переключение.
 */
class CafeResource extends Resource
{
    protected static ?string $model = Cafe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Кофейня';

    protected static ?string $modelLabel = 'кофейня';

    protected static ?string $pluralModelLabel = 'кофейня';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(150),
                TextInput::make('address')
                    ->label('Адрес')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Название'),
                TextColumn::make('address')->label('Адрес')->placeholder('—'),
                TextColumn::make('users_count')->label('Сотрудников')->counts('users'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * @return Builder<Cafe>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereKey(Auth::user()?->cafe_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCafes::route('/'),
        ];
    }
}
