<?php

namespace App\Filament\Resources\CreditCards;

use App\Filament\Resources\CreditCards\Pages\CreateCreditCard;
use App\Filament\Resources\CreditCards\Pages\EditCreditCard;
use App\Filament\Resources\CreditCards\Pages\ListCreditCards;
use App\Filament\Resources\CreditCards\Pages\ManageCreditCards;
use App\Filament\Resources\CreditCards\Pages\ViewCreditCard;
use App\Filament\Resources\CreditCards\Schemas\CreditCardForm;
use App\Filament\Resources\CreditCards\Schemas\CreditCardInfolist;
use App\Filament\Resources\CreditCards\Tables\CreditCardsTable;
use App\Models\CreditCard;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CreditCardResource extends Resource
{
    protected static ?string $model = CreditCard::class;

    protected static string|BackedEnum|null $navigationIcon = Iconoir::CreditCard;

    public static function getModelLabel(): string
    {
        return __('custom.title.credit_cards');
    }

    public static function getNavigationLabel(): string
    {
        return __('custom.title.credit_cards');
    }

    public static function getPluralLabel(): ?string
    {
        return __('custom.title.credit_cards');
    }

//    public static function getNavigationGroup(): string|UnitEnum|null
//    {
//        return __('custom.title.settings');
//    }

    protected static ?int $navigationSort = 4;

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'account.name'
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nome' => $record->name,
            'Conta'  => $record->account->name
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return CreditCardForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CreditCardInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditCardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
//            'index' => ListCreditCards::route('/'),
//            'create' => CreateCreditCard::route('/create'),
//            'view' => ViewCreditCard::route('/{record}'),
//            'edit' => EditCreditCard::route('/{record}/edit'),
            'index' => ManageCreditCards::route('/'),
        ];
    }
}
