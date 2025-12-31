<?php

namespace App\Filament\Resources\Accounts;

use App\Enums\Icon\Ionicons;
use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Accounts\Pages\ManageAccounts;
use App\Filament\Resources\Accounts\Schemas\AccountForm;
use App\Filament\Resources\Accounts\Tables\AccountsTable;
use App\Filament\Resources\Accounts\Tables\InstallmentsTable;
use App\Models\Account;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Iconoir::Wallet;


    public static function getModelLabel(): string
    {
        return __('custom.title.accounts');
    }

    public static function getNavigationLabel(): string
    {
        return __('custom.title.accounts');
    }

    public static function getPluralLabel(): ?string
    {
        return __('custom.title.accounts');
    }

    protected static ?int $navigationSort = 2;


    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('custom.title.settings');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nome' => $record->name,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
         return AccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAccounts::route('/'),
        ];
    }
}
