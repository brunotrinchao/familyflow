<?php

namespace App\Filament\Actions;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Installments\Schemas\InstallmentFormModal;
use App\Filament\Resources\Transactions\Schemas\TransactionInfolistModal;
use App\Filament\Resources\Transactions\Schemas\TransactionTransferFormModal;
use App\Filament\Resources\Transactions\Schemas\TransactionTransferInfolistModal;
use App\Helpers\GeneralHelper;
use App\Helpers\MaskHelper;
use App\Models\Installment;
use App\Models\Transaction;
use App\Services\InstallmentGenerationService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Stripe\Service\TransferService;

class InstallmentActions
{
    public static function makeViewInstallment(): ViewAction
    {
        return ViewAction::make()
            ->cancelParentActions()
            ->modal()
            ->modalIcon(fn (Installment $record) => $record->transaction->category->icon)
            ->modalIconColor(fn (Installment $record) => Color::hex($record->transaction->category->color->value))
            ->modalHeading(function (Installment $record): HtmlString {
                $dateFormat = Carbon::parse($record->transaction->date)->format('d/m/Y');
                return new HtmlString($record->transaction->title . "<br/><small>{$dateFormat}</small>");
            })
            ->extraAttributes(['style' => 'display:none;']) // Geralmente usado para disparar via linha
            ->modalWidth(Width::Medium)
            ->schema(fn ($record,
                         $schema) => $record->transaction->type == TransactionTypeEnum::TRANSFER ? TransactionTransferInfolistModal::configure($schema) : TransactionInfolistModal::configure($schema))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalFooterActions(function ($record) {
                if ($record->transaction->type == TransactionTypeEnum::TRANSFER) {
                    return [
                        static::makeEditTransferInstallment(),
                        static::makeDeleteTransferInstallment($record->id),
                    ];
                }
                return [
                    static::makeEditInstallment(),
                    static::makeDeleteInstallment(),
                ];
            });
    }

    protected static function makeEditInstallment(): EditAction
    {
        return EditAction::make('edit-transaction-modal')
            ->cancelParentActions()
            ->modalHeading("Editar")
            ->model(Transaction::class)
            ->modalWidth(Width::Medium)
            ->modalIcon('heroicon-o-pencil-square')
            ->label('')
            ->tooltip('Editar')
            ->color('primary')
            ->fillForm(function (Installment $record) {
                $data = $record->toArray();
                $tx = $record->transaction;

                if ($tx->account_id) $data['source_custom'] = "account__{$tx->account_id}";
                if ($tx->credit_card_id) $data['source_custom'] = "credit_card__{$tx->credit_card_id}";

                $data['category_id'] = $tx->category_id;
                $data['type'] = $tx->type->value;
                $data['amount'] = abs($record->amount);
                $data['update_mode'] = 'update_only_this';

                if ($tx->credit_card_id) {
                    $data['invoice'] = $record->invoice->period_date->translatedFormat('Y-m');
                }
                return $data;
            })
            ->schema(fn ($schema, $record) => InstallmentFormModal::configure($schema, $record->transaction->type))
            ->action(function (array $data, Installment $record, $livewire) {
                try {
                    DB::beginTransaction();
                    [
                        $source,
                        $sourceId
                    ] = GeneralHelper::parseSource($data['source_custom'] ?? '');
                    $rawAmount = MaskHelper::covertStrToInt($data['amount']);

                    $data['amount'] = match (TransactionTypeEnum::tryFrom($data['type'])) {
                        TransactionTypeEnum::EXPENSE => -abs($rawAmount),
                        TransactionTypeEnum::INCOME => abs($rawAmount),
                        default => throw new \Exception("Tipo inválido"),
                    };

                    $data['source'] = $source;
                    if ($source === TransactionSourceEnum::ACCOUNT) $data['account_id'] = $sourceId;
                    if ($source === TransactionSourceEnum::CREDIT_CARD) $data['credit_card_id'] = $sourceId;

                    app(InstallmentGenerationService::class)->update($data, $record);
                    DB::commit();

                    Notification::make()->title('Sucesso')->success()->send();
                    $livewire->dispatch('refresh-page');
                } catch (\Exception $e) {
                    DB::rollBack();
                    report($e);
                    Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                }
            });
    }

    protected static function makeDeleteInstallment(): Action
    {
        return Action::make('delete-transaction-modal')
            ->cancelParentActions()
            ->label('')
            ->visible(function (Installment $record) {
                return ($record->status !== InstallmentStatusEnum::PAID);
            })
            ->modalWidth(Width::Medium)
            ->tooltip('Excluir lançamento')
            ->modalHeading(fn ($record) => new HtmlString("O lançamento <b>{$record->transaction->title}</b> é uma <b>{$record->transaction->type->getLabel()}</b>. O que deseja fazer?"))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modalSubmitAction(false)
            ->modalFooterActions(fn (Installment $record) => [
                static::makeDeleteSubAction($record, 'only_this'),
                static::makeDeleteSubAction($record, 'future'),
            ]);
    }

    protected static function makeDeleteTransferInstallment($name): Action
    {
        return Action::make('delete-transaction-transfer-modal' . $name)
            ->cancelParentActions()
            ->requiresConfirmation()
            ->label('')
            ->modalWidth(Width::Medium)
            ->tooltip('Excluir Transferência')
            ->modalHeading(fn ($record) => new HtmlString("O lançamento <b>{$record->transaction->description}</b> é uma <b>{$record->transaction->type->getLabel()} Entre contas</b>. O que deseja fazer?"))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modalSubmitActionLabel('Excluir transferência')
            ->action(function ($record, $livewire) {
                try {
                    app(TransactionService::class)->delete($record->transaction);

                    Notification::make()->title('Excluído')->success()->send();
                    $livewire->dispatch('refresh-page');
                } catch (\Exception $e) {
                    Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                }
            })
            //            ->modalFooterActions(function (Installment $record) use ($name) {
            //
            //                $label = 'Excluir transferência';
            //
            //                return Action::make()
            //                    ->cancelParentActions()
            //                    ->label($label)
            //                    ->color('danger')
            //                    ->extraAttributes(['class' => 'w-full'])
            ////                    ->action(function ($livewire) use ($record) {
            ////                        try {
            ////                            dd($record);
            ////                            app(TransactionService::class)->delete();
            ////
            ////                            Notification::make()->title('Excluído')->success()->send();
            ////                            $livewire->dispatch('refresh-page');
            ////                        } catch (\Exception $e) {
            ////                            Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
            ////                        }
            ////                    })
            //                    ;
            //            })
            ;
    }

    protected static function makeEditTransferInstallment(): EditAction
    {
        return EditAction::make('edit-transaction-transfer-modal')
            ->cancelParentActions()
            ->modalHeading("Editar")
            ->model(Transaction::class)
            ->modalWidth(Width::Medium)
            ->modalIcon('heroicon-o-pencil-square')
            ->label('')
            ->tooltip('Editar')
            ->color('primary')
            ->fillForm(function (Installment $record) {
                $data = $record->transaction->toArray();
                $data['amount'] = abs($data['amount']);
                $data['source_custom'] = "account__{$data['account_id']}";
                $data['account_destine'] = "account__{$data['destination_account_id']}";
                return $data;
            })
            ->schema(fn ($schema, $record) => TransactionTransferFormModal::configure($schema))
            ->action(function (array $data, Installment $record, $livewire) {
                try {
                    DB::beginTransaction();
                    [
                        $source,
                        $sourceId
                    ] = GeneralHelper::parseSource($data['source_custom']);

                    [
                        $sourceDestine,
                        $sourceDestineId
                    ] = GeneralHelper::parseSource($data['account_destine']);
                    $data['destination_account_id'] = $sourceDestineId;

                    //                    $data['category_id'] = Category::where('icon', 'data-transfer-both')->first()->id;

                    $data['source'] = $source;
                    $data['amount'] = MaskHelper::covertStrToInt($data['amount']);
                    $data['installment_number'] = $data['is_recurring'] ? $data['installment_number'] : 1;

                    match ($source) {
                        TransactionSourceEnum::ACCOUNT => $data['account_id'] = $sourceId,
                        TransactionSourceEnum::CREDIT_CARD => $data['credit_card_id'] = $sourceId,
                        default => throw new \Exception("Fonte de transação inválida"),
                    };

                    unset($data['source_custom'], $data['is_recurring'], $data['account_destine']);

                    app(TransactionService::class)->update($record->transaction, $data);

                    $livewire->dispatch('refreshInstallments');

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    report($e);
                    Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                }
            });
    }

    private static function makeDeleteSubAction(Installment $record, string $mode): Action
    {
        $isMultiple = $record->transaction->installment_number > 1;
        if ($mode === 'future' && !$isMultiple) return Action::make('hidden')->visible(false);

        $label = $mode === 'only_this'
            ? ($isMultiple ? 'Excluir apenas este' : 'Excluir lançamento')
            : 'Excluir este e os próximos';

        return Action::make('confirm_delete_' . $mode)
            ->cancelParentActions()
            ->label($label)
            ->color($mode === 'only_this' ? 'gray' : 'danger')
            ->extraAttributes(['class' => 'w-full'])
            ->action(function ($livewire) use ($record, $mode, $isMultiple) {
                try {
                    $toDelete = $mode === 'only_this'
                        ? $record
                        : $record->transaction->installments()->where('number', '>=', $record->number)->get();

                    app(InstallmentGenerationService::class)->delete($toDelete);

                    Notification::make()->title('Excluído')->success()->send();
                    $livewire->dispatch('refresh-page');
                } catch (\Exception $e) {
                    Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
