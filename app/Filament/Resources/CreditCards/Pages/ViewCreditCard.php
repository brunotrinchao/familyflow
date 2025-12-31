<?php

namespace App\Filament\Resources\CreditCards\Pages;

use App\Filament\Resources\CreditCards\CreditCardResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCreditCard extends ViewRecord
{
    protected static string $resource = CreditCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
