<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\CategoryIconEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Transactions\Schemas\TransactionFormInvoiceModal;
use App\Filament\Resources\Transactions\Schemas\TransactionFormModal;
use App\Helpers\GeneralHelper;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Category;
use App\Models\Invoice;
use App\Services\InvoicesService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class InvoiceInfolist
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(5)
                    ->columns([
                        'default' => 2,
                        'lg'      => 5,
                    ])
                    ->schema([
                        TextEntry::make('invoice_total')
                            ->label('Fatura Total')
                            ->money('BRL')
                            ->extraEntryWrapperAttributes(['class' => 'justify-center'])
                            ->size(TextSize::Large)
                            ->weight(FontWeight::ExtraBold)
                            ->getStateUsing(function (mixed $record): string {
                                return MaskHelper::covertIntToReal(abs($record->total_amount));
                            })
                            ->columnSpan(1),
                        TextEntry::make('close_day')
                            ->numeric()
                            ->label('Fechamento')
                            ->extraEntryWrapperAttributes(['class' => 'justify-center'])
                            ->size(TextSize::Large)
                            ->weight(FontWeight::ExtraBold)
                            ->getStateUsing(function (mixed $record) {
                                $date = Carbon::parse($record->period_date);
                                return Carbon::create($date->year, $date->month, $record->creditCard->closing_day)->format('d/m/Y');
                            })
                            ->columnSpan(1),
                        TextEntry::make('due_day')
                            ->numeric()
                            ->extraEntryWrapperAttributes(['class' => 'justify-center'])
                            ->alignCenter()
                            ->size(TextSize::Large)
                            ->label('Vencimento')
                            ->placeholder('-')
                            ->weight(FontWeight::ExtraBold)
                            ->getStateUsing(function (mixed $record) {
                                $date = Carbon::parse($record->period_date);
                                return Carbon::create($date->year, $date->month, $record->creditCard->due_day)->format('d/m/Y');
                            })
                            ->columnSpan(1),
                        TextEntry::make('invoice_total')
                            ->label('Valor a Pagar')
                            ->extraEntryWrapperAttributes(['class' => 'justify-center'])
                            ->alignCenter()
                            ->size(TextSize::Large)
                            ->money('BRL')
                            ->weight(FontWeight::ExtraBold)
                            ->getStateUsing(function (mixed $record): string {
                                $total = $record->installments()
                                    ->where('status', '!=', InstallmentStatusEnum::PAID)->sum('amount');
                                return MaskHelper::covertIntToReal(abs($total));
                            })
                            ->columnSpan(1),
                        Actions::make([
                            Action::make('payment_cancel')
                                ->visible(fn (Model $record) => $record->status == InvoiceStatusEnum::PAID)
                                ->tooltip('Cancelar pagamento')
                                ->label('Cancelar pagamento')
                                ->color(Color::Neutral)
                                //                                            ->icon(Iconoir::ThumbsDown)
                                ->modalIcon(Iconoir::Notes)
                                ->requiresConfirmation()
                                ->modalHeading('Cancelar pagamento')
                                ->modalDescription('Tem certeza que quer cancelar o pagamento da fatura?')
                                ->action(function (Invoice $record) {
                                    try {
                                        $invoiceService = app(InvoicesService::class);

                                        $invoiceService->processCancelInvoicePayment($record);

                                        Notification::make()
                                            ->title('Pagamento Cancelado com Sucesso')
                                            ->body("A fatura #{$record->id} e suas transações foram revertidas.")
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Falha ao Cancelar Pagamento')
                                            // Usa a mensagem da exceção lançada no Service
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->persistent() // Mantém a notificação visível
                                            ->send();
                                    }
                                }),

                            Action::make('payment')
                                ->label(fn (Model $record) => $record->status == InvoiceStatusEnum::CLOSED ? 'Pagar' : 'Antecipar Pagamento')
                                ->visible(fn (Model $record) => $record->status != InvoiceStatusEnum::PAID)
                                ->size(Size::Small)
                                ->modalWidth(Width::Medium)
                                ->modalIcon(Iconoir::Notes)
                                ->modal()
                                ->modalHeading(fn (Model $record) => $record->status == InvoiceStatusEnum::CLOSED ? 'Pagar Fatura' : 'Antecipar Pagamento')
                                ->schema(fn (Schema $schema) => InvoiceFormModal::configure($schema, TransactionTypeEnum::EXPENSE))
                                ->fillForm(function (Model $record) {
                                    $date = Carbon::parse($record->period_date);
                                    $monthYearLabel = $date->translatedFormat('F \d\e Y');
                                    return [
                                        'description'   => "Pagamento fatura ({$monthYearLabel})",
                                        'amount'        => abs($record->total_amount),
                                        'date'          => Carbon::now(),
                                        'category_id'   => Category::where('icon', CategoryIconEnum::CreditCardPayment)->first()->id,
                                        'status'        => true,
                                        'source_custom' => "account__{$record->creditCard->account_id}"
                                    ];
                                })
                                ->tooltip(fn (Model $record) => $record->status == InvoiceStatusEnum::CLOSED ? 'Pagar Fatura' : 'Antecipar Pagamento')
                                ->color(Color::Green)
                                ->action(function (array $data, Invoice $record) {

                                    try {
                                        $amount = MaskHelper::covertStrToInt($data['amount']);
                                        $amountTotal = abs($record->total_amount);

                                        [
                                            $source,
                                            $sourceId
                                        ] = GeneralHelper::parseSource($data['source_custom']);

                                        $calcTotal = $amountTotal - $amount;
                                        $data['amount_rest'] = $calcTotal;
                                        $data['amount'] = $amount;
                                        $data['account_id'] = $sourceId;

                                        $account = Account::find($sourceId);

                                        $invoiceService = app(InvoicesService::class);
                                        $invoiceService->confirmPayment($record, $account, $data['amount']);


                                        Notification::make()
                                            ->title('Pagamento realizado com Sucesso')
                                            ->body("A fatura foi paga com sucesso.")
                                            ->success()
                                            ->send();

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Falha ao realizar Pagamento')
                                            // Usa a mensagem da exceção lançada no Service
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->persistent() // Mantém a notificação visível
                                            ->send();
                                    }

                                    //                                                        $transaction = $record->installments->first()->transaction;

                                    //                                                        TransactionService::updateStatus($transaction, TransactionStatusEnum::POSTED);
                                    // TODO - Criar um metodo no servico invoice para receber os dados, atualizar a invoice e os installments e em seguida criar as transaction de pagamento
                                    //                                                        dd($data, $record);
                                })
                                ->extraAttributes([
                                    // 'w-full' torna o botão 100% de largura
                                    // 'sm:w-auto' devolve a largura automática em telas maiores que mobile
                                    'class' => 'w-full justify-center',
                                ]),
                        ])
                            ->columnSpan([
                                'md' => 'full',
                                'lg' => 1,
                            ])
                            ->alignCenter()
                            ->extraAttributes(['class' => 'flex items-center justify-center align-middle'])

                    ])
                    //                    ->extraAttributes(['class' => 'flex items-center align-middle w-full justify-between'])
                    ->columnSpanFull()


            ]);
    }
}
