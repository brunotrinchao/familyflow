<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Enums\AccountTypeEnum;
use App\Enums\BankEnum;
use App\Helpers\MaskHelper;
use App\Models\Brand;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->label('Banco')
                    ->native(false)
                    ->allowHtml()
                    ->relationship(name: 'brand', titleAttribute: 'name', modifyQueryUsing: function (Builder $query) {
                        $query->where('type', 'BANK');
                    })
                    ->getOptionLabelFromRecordUsing(function (Brand $record): string|HtmlString {
                        if ($record->icon_path) {
                            // Constrói a URL pública usando o disco 'public'
                            $imageUrl = asset('storage/' . $record->icon_path);

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
                    ->label('Nome da conta')
                    ->helperText('Dê um nome para identificar esta conta')
                    ->required(),
                TextInput::make('balance')
                    ->label('Saldo') // É uma boa prática avisar, já que o BD armazena em int
                    ->mask(MaskHelper::maskMoney())
                    ->prefix('R$')
                    ->default(0)
                    ->required()
                    ->minValue(0)
            ]);
    }
}
