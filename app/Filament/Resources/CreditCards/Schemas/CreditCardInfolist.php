<?php

namespace App\Filament\Resources\CreditCards\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CreditCardInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('family_id')
                    ->numeric(),
                TextEntry::make('name'),
                TextEntry::make('bank')
                    ->badge(),
                TextEntry::make('last_four_digits')
                    ->placeholder('-'),
                TextEntry::make('closing_day')
                    ->numeric(),
                TextEntry::make('due_day')
                    ->numeric(),
                TextEntry::make('credit_limit')
                    ->numeric(),
                TextEntry::make('credit_used')
                    ->numeric(),
                IconEntry::make('status')
                    ->boolean(),
                TextEntry::make('card_holder_user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
