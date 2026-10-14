<?php

namespace App\Filament\Resources\PurchaseRequests;

use App\Enums\RequestStatus;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestInfolist;
use App\Filament\Resources\PurchaseRequests\Tables\PurchaseRequestsTable;
use App\Models\PurchaseRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Заявки';

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'заявки';

    protected static ?int $navigationSort = 1;

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof PurchaseRequest ? $record->title() : 'Заявка';
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestsTable::configure($table);
    }

    /**
     * @return Builder<PurchaseRequest>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['section', 'author', 'items']);
    }

    // Заявки подают сотрудники из кабинета; удалять нельзя — только отменять.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Сколько новых заявок ждут управляющего.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = PurchaseRequest::query()->where('status', RequestStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseRequests::route('/'),
            'view' => ViewPurchaseRequest::route('/{record}'),
            'edit' => EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
