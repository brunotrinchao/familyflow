<?php

namespace App\Filament\Resources\Transactions;

use App\Enums\Icon\Ionicons;
use App\Enums\TransactionStatusEnum;
use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;
use App\Filament\Resources\Transactions\RelationManagers\TransactionSeriesRelationManager;
use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Resources\Transactions\Schemas\TransactionInfolist;
use App\Filament\Resources\Transactions\Tables\TransactionsTable;
use App\Helpers\MaskHelper;
use App\Models\Transaction;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Iconoir::DataTransferWarning;

    protected static int $globalSearchResultsLimit = 20;

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'amount',
            'description',
            'familyUser.user.name',
            'category.name',
            'source',
            'type',
            'date'
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Valor' => MaskHelper::covertIntToReal($record->amount),
            'Data'  => Carbon::create($record->date)->format('d/m/Y'),
            'Tipo'  => $record->type->getLabel() . ' - (' . $record->category->name . ')',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->description;
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('custom.title.releases');
    }

    public static function getNavigationLabel(): string
    {
        return __('custom.title.releases');
    }

    public static function getPluralLabel(): ?string
    {
        return __('custom.title.releases');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionSeriesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTransactions::route('/'),
            'create' => CreateTransaction::route('/create'),
            'view'   => ViewTransaction::route('/{record}'),
            'edit'   => EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function canView(?Model $record): bool
    {
        if (is_null($record)) {
            return false;
        }

        $tenantId = Filament::getTenant()?->id;
        if (!$tenantId) {
            return false;
        }

        if (isset($record->family_id)) {
            return $record->family_id === $tenantId;
        }

        return $record->familyUser?->family_id === $tenantId;
    }

}
