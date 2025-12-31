<?php

namespace App\Filament\Actions;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Installments\Schemas\InstallmentFormModal;
use App\Filament\Resources\Transactions\Schemas\TransactionInfolistModal;
use App\Helpers\GeneralHelper;
use App\Helpers\MaskHelper;
use App\Models\Installment;
use App\Models\Transaction;
use App\Services\InstallmentGenerationService;
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
            ->schema(fn ($schema) => TransactionInfolistModal::configure($schema))
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalFooterActions([
                static::makeEditInstallment(),
                static::makeDeleteInstallment(),
            ]);
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
                    [$source, $sourceId] = GeneralHelper::parseSource($data['source_custom'] ?? '');
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
            ->visible(fn (Installment $record) => $record->status !== InstallmentStatusEnum::PAID)
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
