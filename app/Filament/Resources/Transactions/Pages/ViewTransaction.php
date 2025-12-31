<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\Schemas\TransactionFormModal;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getListeners(): array
    {
        return [
            'refreshTransactionSeries' => '$refresh',
        ];
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_transaction')
                ->color(Color::Amber)
                ->label('Editar')
                ->size(Size::ExtraLarge)
                ->icon(Heroicon::Pencil)
                ->button()
                ->modalWidth(Width::Small)
                ->modalSubmitActionLabel('Salvar')
                ->fillForm(function ($record) {
                    $data = $record->toArray();
                    $data['is_recurring'] = $data['installment_number'] ? true : false;

                    return $data;
                })
                ->schema(fn (Schema $schema) => TransactionFormModal::configure($schema))
                ->action(function (array $data, Transaction $record, \Filament\Actions\Action $action): void {
                    $transactionUpdated = TransactionService::update($record, $data);

                    if ($transactionUpdated) {
                        Notification::make('createSuccess')
                            ->title('Transação atualizada com sucesso!')
                            ->success()
                            ->send();
                    }
                })
                ->before(function (Model $record, array $data, Action $action) {
                    if (!TransactionService::validateUpdate($record, $data)) {
                        Notification::make()
                            ->title('Verifique os dados inseridos da trasação.')
                            ->body(Str::markdown('***Regras***
                                            * O novo valor não pode ser menor que a soma das parcelas lançadas.
                                            * o número de parcelas não pode ser menos que as parcelas lançadas.
                                            '))
                            ->warning()
                            ->send();

                        $action->halt();
                    }

                })
                ->after(function (Component $livewire) {
                    $livewire->dispatch('refreshTransactionSeries');
                }),
            Action::make('return_transaction')
                ->icon(Heroicon::ArrowLeft)
                ->size(Size::ExtraLarge)
                ->color(Color::Slate)
                ->url(fn (Transaction $record): string => TransactionResource::getUrl('index'))
            ->label('Voltar')
        ];
    }
}
