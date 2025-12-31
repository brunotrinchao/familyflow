<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\Icon\Ionicons;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\GeneralHelper;
use App\Helpers\MaskHelper;
use App\Models\Transaction;
use App\Services\TransactionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;

class TransactionFormChoice
{
    /**
     * @param Schema $schema
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        // Define os tipos de transação que serão usados
        $transactionTypes = [
            TransactionTypeEnum::INCOME,
            TransactionTypeEnum::EXPENSE,
            TransactionTypeEnum::TRANSFER,
        ];

        // 1. Gera dinamicamente o array de Actions
        $actions = array_map(function (TransactionTypeEnum $type) {
            return self::buildTransactionAction($type);
        }, $transactionTypes);


        return $schema->components([
            Grid::make(1) // Ajusta o grid para o número de ações
            ->schema($actions)
                ->columnSpanFull()
        ]);
    }

    // 2. Função auxiliar para construir cada CreateAction (DRY)
    private static function buildTransactionAction(TransactionTypeEnum $type): Action
    {
        return Action::make($type->value . 'Creation')
            ->model(Transaction::class)
            ->extraAttributes(['class' => 'w-full'])
            ->modalIcon($type->getIcon())
            ->modalHeading($type->getLabel())
            ->label($type->getLabel())
            ->icon($type->getIcon())
            ->color($type->getColor())
            ->size(Size::Large)
            ->modal()
            ->modalWidth(Width::Medium)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->schema(function (Schema $schema) use ($type) {

                return match ($type) {
                    TransactionTypeEnum::INCOME,
                    TransactionTypeEnum::EXPENSE => TransactionFormModal::configure($schema, $type),
                    TransactionTypeEnum::TRANSFER => TransactionTransferFormModal::configure($schema),
                };

            })
            ->action(function (array $data, Action $action) use ($type) {
                // Lógica de salvamento aqui (mesma que você já tem)
                try {

                    [
                        $source,
                        $sourceId
                    ] = GeneralHelper::parseSource($data['source_custom']);

                    $data['type'] = $type;
                    $data['source'] = $source;
                    $data['status'] = $data['status'] ? TransactionStatusEnum::PAID : TransactionStatusEnum::PENDING;
                    $data['amount'] = MaskHelper::covertStrToInt($data['amount']);
                    $data['installment_number'] = $data['is_recurring'] ? $data['installment_number'] : 1;

                    match ($source) {
                        TransactionSourceEnum::ACCOUNT => $data['account_id'] = $sourceId,
                        TransactionSourceEnum::CREDIT_CARD => $data['credit_card_id'] = $sourceId,
                        default => throw new \Exception("Fonte de transação inválida"),
                    };

                    unset($data['source_custom'], $data['is_recurring']);;

                    $result = app(TransactionService::class)->create($data);
                    self::validateTransactionResult($result);

                    // Fecha o modal e dá refresh na página/tabela
                    $action->getLivewire()->dispatch('refreshInstallments'); // Opcional: emite evento de atualização
                } catch (\Exception $e) {
                    Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                    $action->halt();
                }
            });
    }

    // 4. Função de validação unificada (para todos os tipos)
    private static function validateTransactionResult($result): void
    {
        // Verifica se $result é válido (pode ser um objeto Transaction, um array de Transactions, ou um valor booleano positivo)
        if ($result && (is_object($result) || (is_array($result) && count($result) > 0))) {
            Notification::make()
                ->title('Transação Criada')
                ->body('O lançamento foi registrado com sucesso.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Erro ao Criar Transação')
                ->body('Não foi possível registrar o lançamento. Tente novamente.')
                ->danger()
                ->send();
        }
    }
}
