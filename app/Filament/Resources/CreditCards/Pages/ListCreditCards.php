<?php

namespace App\Filament\Resources\CreditCards\Pages;

use App\Filament\Resources\CreditCards\CreditCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCreditCards extends ListRecords
{
    protected static string $resource = CreditCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
