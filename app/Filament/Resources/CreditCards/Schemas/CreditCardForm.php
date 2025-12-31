<?php

namespace App\Filament\Resources\CreditCards\Schemas;

use App\Enums\BankEnum;
use App\Enums\ProfileUserEnum;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Brand;
use App\Models\CreditCardBrand;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CreditCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->label('Bandeira do Cartão')
                    ->native(false)
                    ->allowHtml()
                    ->relationship(name: 'brand', titleAttribute: 'name', modifyQueryUsing: function (Builder $query) {
                        $query->where('type', 'CREDITCARD');
                    })
                    ->getOptionLabelFromRecordUsing(function (Brand $record): string|HtmlString {
                        if ($record->icon_path) {
                            // Constrói a URL pública usando o disco 'public'
                            $imageUrl = asset('storage/' . $record->icon_path);

                            // Retorna o HTML que inclui a imagem e o nome da bandeira
                            return new HtmlString(
                                "<span class='flex items-center space-x-2'>
<span class='w-8 h-8 overflow-hidden rounded-full flex justify-center align-middle me-3' style='overflow: hidden;'>
                                        <img src='{$imageUrl}' class='object-cover' style='min-width: 100%; min-height: 100%; object-fit: cover' alt='{$record->name}' />
                                        </span>
                                        <small class='ml-4 mute fi-color-gray'> {$record->name}</small>
                                    </span>"
                            );
                        }
                        return $record->name;
                    })
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                Grid::make(2)
                    ->schema([
                        TextInput::make('limit')
                            ->label('Limite')
                            ->prefix('R$')
                            ->mask(MaskHelper::maskMoney())
                            ->required()
                            ->default(0),

                        TextInput::make('last_four_digits')
                            ->label('4 últimos dígitos')
                            ->numeric()
                            ->required()
                            ->maxLength(4),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('closing_day')
                            ->label('Fecha dia')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31),
                        TextInput::make('due_day')
                            ->label('Vence dia')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31),
                    ]),
                Select::make('account_id')
                    ->label('Conta de pagamento')
                    ->placeholder('Selecione')
                    ->options(fn () => Account::all()->pluck('name', 'id'))
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('status')
                    ->required()
                    ->visible(fn (string $operation) => $operation === 'edit'),
            ]);
    }
}
