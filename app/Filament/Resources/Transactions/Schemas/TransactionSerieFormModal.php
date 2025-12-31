<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionSourceEnum;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Helpers\MaskHelper;
use App\Models\Category;
use App\Services\CategoryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\Collection;

class TransactionSerieFormModal
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('installment')
                    ->label('N° Parcela')
                    ->required()
                    ->numeric()
                    ->disabled(function (string $operation): bool {
                        return $operation === 'edit';
                    })
                ->visible(function (string $operation): bool {
                        return $operation === 'edit';
                    }),
                TextInput::make('amount')
                    ->label('Valor') // É uma boa prática avisar, já que o BD armazena em int
                    ->mask(MaskHelper::maskMoney())
                    ->required()
                    ->minValue(0)
                    ->default(0),
                DatePicker::make('date')
                    ->required(),
                Select::make('status')
                    ->options(TransactionStatusEnum::class)
                    ->default('Pending')
                    ->required(),
            ]);
    }
}
