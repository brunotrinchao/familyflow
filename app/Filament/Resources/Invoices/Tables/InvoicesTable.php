<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatusEnum;
use App\Filament\Resources\Transactions\Schemas\TransactionFormChoice;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Brand;
use App\Models\CreditCard;
use App\Services\AiInvoiceScannerService;
use App\Services\InvoicesService;
use App\Services\PdfInvoiceParserService;
use Carbon\Carbon;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Stripe\Service\InvoiceService;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('credit_card')
                    ->label('')
                    ->getStateUsing(function (mixed $record): HtmlString {

                        $brandName = $record->creditCard->name ?? 'Desconhecido';

                        $imageUrl = asset('storage/' . $record->creditCard->brand->icon_path);

                        $renderedHtml = view('components.source-icon-view', [
                            'image' => $imageUrl,
                            'brand' => $brandName,
                        ])->render();

                        return new HtmlString(Blade::render($renderedHtml));
                    }),
                TextColumn::make('period_date')
                    ->label('')
                    ->getStateUsing(function (Model $record) {
                        $date = Carbon::parse($record->period_date);
                        $monthYearLabel = Str::ucfirst($date->translatedFormat('F \d\e Y'));
                        return $monthYearLabel;
                    }),
                TextColumn::make('total_amount')
                    ->label('')
                    ->money('BRL')
                    ->color(fn (Model $record) => MaskHelper::amountColor($record->total_amount))
                    ->getStateUsing(function (mixed $record): string {
                        return MaskHelper::covertIntToReal($record->total_amount);
                    })
                    ->summarize(
                    [
                        Sum::make()
                            ->money('BRL', locale: 'pt_BR', divideBy: 100)
                            ->label('Valor total'),
                    ]),
                TextColumn::make('status')
                    ->label('')
                    ->icon(false)
                    ->badge()
            ])
            ->filters([
                Filter::make('filter')
                    ->label('')
                    ->schema([
                        Grid::make(1)
                            ->columnSpanFull()
                            ->schema([
                                Flatpickr::make('period_date')
                                    ->label('Período')
                                    //                                ->disableMobile(false)
                                    ->displayFormat('F / Y')
                                    ->defaultDate(Carbon::now()->startOfMonth())
                                    ->locale('pt')
                                    ->format('Y-m')
                                    ->monthPicker()
                                    ->default(Carbon::now()->startOfMonth()->format('Y-m'))
                                    ->columnSpan(1),
                                Select::make('status')
                                    ->label('Status')
                                    ->placeholder('Selecione')
                                    ->options(InvoiceStatusEnum::class)
                                    ->columnSpan(1),
                                Select::make('creditCard')
                                    ->label('Cartão de credito')
                                    ->placeholder('Selecione')
                                    ->relationship('creditCard', 'name')
                                    ->columnSpan(1),
                            ])
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        $dates = $data['period_date'] ?? null;
                        $status = $data['status'] ?? null;
                        $creditCard = $data['creditCard'] ?? null;

                        $query->when($status, fn ($q, $v) => $q->where('status', $v));
                        $query->when($creditCard, fn ($q, $v) => $q->where('credit_card_id', $v));


                        if ($dates) {

                            $dateString = trim($dates);

                            Carbon::setLocale('pt');

                            $startDate = Carbon::parse($dateString);

                            $endDate = $startDate->copy()->endOfMonth()->endOfDay();


                            $query->whereBetween('period_date', [
                                $startDate->copy()->startOfMonth(),
                                $endDate
                            ]);
                        }
                    })
                    ->indicateUsing(function (array $data): ?array {
                        $indicators = [];

                        if (!empty($data['period_date'])) {
                            $dateString = trim($data['period_date']);

                            $carbonDate = Carbon::parse($dateString);

                            $periodoFormatado = $carbonDate->translatedFormat('F \d\e Y');

                            $indicators[] = Indicator::make('Período: ' . mb_ucfirst($periodoFormatado))
                                ->removable(false);
                        }

                        if (!empty($data['status'])) {
                            $status = InvoiceStatusEnum::from($data['status']);
                            $indicators[] = Indicator::make('Status: ' . $status->getLabel())
                                ->removeField('status');
                        }

                        if (!empty($data['creditCard'])) {
                             $creditCard = CreditCard::find($data['creditCard']);
                            $indicators[] = Indicator::make('Cartão: ' . $creditCard->name)
                                ->removeField('creditCard');
                        }

                        return $indicators;
                    })
            ], FiltersLayout::Modal)
            ->deferFilters(false)
            ->filtersFormColumns(1)
            ->defaultSort('period_date')
            ->headerActions([
                Action::make('import_invoice')
                    ->modalIcon(Iconoir::Sparks)
                    ->modalHeading('Importar Fatura')
                    ->label('Importar Fatura')
                    ->icon(Iconoir::Sparks)
                    ->color('primary')
                    ->button()
                    ->size(Size::ExtraLarge)
                    ->modal()
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->schema([
                        Wizard::make([
                            // ETAPA 1: UPLOAD
                            Step::make('Upload')
                                ->icon(Iconoir::Upload)
                                ->schema([
                                    Grid::make(12)->schema([
                                        FileUpload::make('attachment')
                                            ->label('Arquivo da Fatura')
                                            ->directory('temp-invoices')
                                            ->acceptedFileTypes(['application/pdf'])
                                            //                                            ->required()
                                            ->live()
                                            ->columnSpanFull()
                                            ->afterStateUpdated(function ($state, Set $set, $livewire) {
                                                if (!$state instanceof TemporaryUploadedFile) return;

                                                try {
                                                    $dto = app(PdfInvoiceParserService::class)->parse($state->getRealPath());

                                                    // Preenche os dados para os próximos passos

                                                    $brandBankId = CreditCard::where('name', $dto->name)
                                                        ->whereRelation('familyUser', 'family_id', Filament::getTenant()->id)
                                                        ->value('brand_id') // Retorna diretamente o ID ou null

                                                        ?? // Se for null, executa a busca abaixo:

                                                        Brand::where('name', 'LIKE', "%{$dto->name}%")
                                                            ->value('id');


                                                    $set('card_name', $dto->name);
                                                    $set('card_brand', $brandBankId ?? $dto->brand);
                                                    $set('card_last_four', $dto->lastFourDigits);
                                                    $set('closing_day', $dto->closingDay);
                                                    $set('due_day', $dto->dueDay);
                                                    $set('card_limit', MaskHelper::covertIntToReal($dto->limit, false));
                                                    $set('card_used', MaskHelper::covertIntToReal($dto->used, false));

                                                    $brandId = Account::where('name', $dto->bankName)
                                                        ->whereRelation('familyUser', 'family_id', Filament::getTenant()->id)
                                                        ->value('brand_id') // Retorna diretamente o ID ou null

                                                        ?? // Se for null, executa a busca abaixo:

                                                        Brand::where('name', 'LIKE', "%{$dto->bankName}%")
                                                            ->value('id');

                                                    if ($brandId) {
                                                        $set('bank_brand', $brandId);
                                                    }
                                                    $set('bank_name', $dto->bankName);
                                                    $set('bank_cnpj', $dto->bankCnpj);
                                                    $set('invoice', $dto->dueDateInvoice);

                                                    $set('items', $dto->transactions
                                                        ->filter(fn ($t) => !$t->isIncome)
                                                        ->map(fn ($t) => [
                                                            'date'                   => $t->date->format('Y-m-d'),
                                                            'description'            => $t->description,
                                                            'amount'                 => $t->amount,
                                                            'is_parcelado'           => $t->isParcelado,
                                                            'parcela_atual'          => $t->parcelaAtual,
                                                            'parcela_total'          => $t->parcelaTotal,
                                                            'first_installment_date' => $t->firstInstallmentDate?->format('Y-m-d'),
                                                        ])->toArray()
                                                    );

                                                    Notification::make()->title('IA processou o PDF com sucesso!')->success()->send();

                                                    $livewire->dispatch('wizard::nextStep', '');
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Erro ao processar' . $e->getMessage())->danger()->send();
                                                }
                                            }),
                                    ])
                                ]),

                            // ETAPA 2: Conta Bancárias
                            Step::make('Conta Bancária')
                                ->icon(Iconoir::Bank)
                                ->schema([

                                    Select::make('bank_brand')->label('Bandeira')->required()
                                        ->options(fn () => Brand::where('type', 'BANK')->pluck('name', 'id')->toArray()),
                                    TextInput::make('bank_name')->label('Banco')->required(),
                                    //                                    TextInput::make('bank_cnpj')->label('CNPJ Emissor'),


                                ])
                                ->columns(2),

                            // ETAPA 3: CARTÃO DE CRÉDITO
                            Step::make('Cartão de Crédito')
                                ->icon(Iconoir::CreditCard)
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('card_name')->label('Nome do Cartão')->required(),
                                            Select::make('card_brand')->label('Bandeira')->required()
                                                ->options(fn () => Brand::where('type', 'CREDITCARD')->pluck('name', 'id')->toArray()),
                                            TextInput::make('card_last_four')->label('Final')->maxLength(4)->required(),
                                        ]),
                                    Grid::make(4)
                                        ->schema([
                                            TextInput::make('card_limit')
                                                ->label('Limite Total')
                                                ->mask(MaskHelper::maskMoney())
                                                ->prefix('R$'),
                                            TextInput::make('card_used')
                                                ->label('Limite usado')
                                                ->mask(MaskHelper::maskMoney())
                                                ->prefix('R$'),
                                            TextInput::make('closing_day')->label('Dia Fechamento')->numeric()->required(),
                                            TextInput::make('due_day')->label('Dia Vencimento')->numeric()->required(),
                                        ]),

                                    Hidden::make('invoice'),

                                ]),

                            // ETAPA 4: TRANSAÇÕES
                            Step::make('Transações')
                                ->icon(Iconoir::TaskList)
                                ->schema([
                                    Repeater::make('items')
                                        ->label('Itens da Fatura')
                                        ->schema([
                                            Grid::make(12)->schema([
                                                DatePicker::make('date')->label('Data')->columnSpan(2)->required(),
                                                TextInput::make('description')
                                                    ->label('Descrição')
                                                    ->columnSpan(7)
                                                    ->required()
                                                    ->suffix(fn ($get) => $get('is_parcelado') ? "{$get('parcela_atual')}/{$get('parcela_total')}" : null),
                                                TextInput::make('amount')->label('Valor')->mask(MaskHelper::maskMoney())->prefix('R$')->columnSpan(3)->required(),

                                                // Metadados ocultos
                                                Hidden::make('is_parcelado'),
                                                Hidden::make('parcela_atual'),
                                                Hidden::make('parcela_total'),
                                                Hidden::make('first_installment_date'),
                                            ])
                                        ])
                                        ->itemLabel(fn (array $state): ?string => ($state['description'] ?? '') .
                                            ($state['is_parcelado'] ? " ({$state['parcela_atual']}/{$state['parcela_total']})" : "")
                                        )
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->columnSpanFull(),
                                ]),
                        ])
                            ->columnSpanFull()
                            ->skippable()
                            ->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                        wire:submit="register"
                    >
                        Importar
                    </x-filament::button>
                    BLADE
                            )))
                    ])
                    ->action(function (array $data) {

                        $invoiceService = app(InvoicesService::class);

                        $data['card_limit'] = MaskHelper::covertStrToInt($data['card_limit']);
                        $data['card_used'] = MaskHelper::covertStrToInt($data['card_used']);
                        $data['items'] = collect($data['items'])->map(function ($item) {
                            $item['amount'] = MaskHelper::covertStrToInt($item['amount']);
                            return $item;
                        });

                        $invoiceService->createInvoiceWithImport($data);
                    })


            ]);
    }
}
