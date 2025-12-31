<?php

namespace App\Filament\Resources\Brands;

use App\Enums\ProfileUserEnum;
use App\Enums\StatusEnum;
use App\Filament\Resources\Brands\Pages\ManageCreditCardBrands;
use App\Models\Brand;
use App\Models\CreditCardBrand;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('custom.title.brand');
    }

    public static function getNavigationLabel(): string
    {
        return __('custom.title.brand');
    }

    public static function getPluralLabel(): ?string
    {
        return __('custom.title.brand');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('custom.title.admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->required()
                    ->options([
                        'BANK'       => 'Banco',
                        'CREDITCARD' => 'Cartão de crédito',
                    ]),
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->label('Status')
                    ->options(StatusEnum::class)
                    ->default(StatusEnum::ACTIVE),

                // 🚨 NOVO: Campo de Upload para o Ícone 🚨
                FileUpload::make('icon_path')
                    ->label('Ícone da Bandeira')
                    ->required()
                    ->image()
                    ->directory(fn ($get) => 'brand/' . Str::slug($get('type')))
                    ->disk('public')
                    ->imageEditor()
                    ->imageEditorEmptyFillColor('#FFFFFF')
                    ->acceptedFileTypes([
                        'image/svg+xml',
                        'image/png',
                        'image/jpeg',
                        'image/webp'
                    ]) // Tipos aceitos
                    ->maxSize(1024)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('CreditCardBrand')
            ->columns([
                ImageColumn::make('icon_path')
                    ->label('Ícone')
                    ->disk('public') // 🚨 CHAVE 1: Define o disco onde a imagem está salva
                    ->visibility('public')
                    ->circular()
                    ->width(30)
                    ->imageHeight(40),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                // ColorColumn::make('color_hex')->label('Cor')->tooltip(fn (CreditCardBrand $record) => $record->color_hex)->copyable(),

                IconColumn::make('status') // O 'status' agora é 'is_active'
                ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCreditCardBrands::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return Auth::user()->isSuperAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user()->fresh();

        return $user->isSuperAdmin();
    }
}
