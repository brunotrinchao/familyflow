<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatusEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('period_date')
                    ->required(),
                TextInput::make('total_amount_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(InvoiceStatusEnum::class)
                    ->default('Pending')
                    ->required(),
            ]);
    }
}
