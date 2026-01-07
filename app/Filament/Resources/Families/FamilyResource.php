<?php

namespace App\Filament\Resources\Families;

use App\Enums\Icon\Ionicons;
use App\Enums\NavigationGroupEnum;
use App\Enums\ProfileUserEnum;
use App\Filament\Resources\Families\Pages\CreateFamily;
use App\Filament\Resources\Families\Pages\EditFamily;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Filament\Resources\Families\Pages\ViewFamily;
use App\Filament\Resources\Families\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Families\Schemas\FamilyForm;
use App\Filament\Resources\Families\Schemas\FamilyInfolist;
use App\Filament\Resources\Families\Tables\FamiliesTable;
use App\Models\Family;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static string|BackedEnum|null $navigationIcon = Iconoir::Group;

    public static function getModelLabel(): string
    {
        return __('custom.title.family');
    }

    public static function getNavigationLabel(): string
    {
        return __('custom.title.family');
    }

    public static function getPluralLabel(): ?string
    {
        return __('custom.title.families');
    }

    protected static ?int $navigationSort = 6;


//    public static function getNavigationGroup(): string|UnitEnum|null
//    {
//        return __('custom.title.settings');
//    }


    public static function form(Schema $schema): Schema
    {
        return FamilyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FamilyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFamilies::route('/'),
            'create' => CreateFamily::route('/create'),
            'view'   => ViewFamily::route('/{record}'),
            'edit'   => EditFamily::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return Auth::user()->isSuperAdmin() || Auth::user()->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user()->fresh();

        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
