<?php

namespace App\Filament\Resources\CreditCards\Pages;

use App\Filament\Resources\CreditCards\CreditCardResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCreditCard extends EditRecord
{
    protected static string $resource = CreditCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
