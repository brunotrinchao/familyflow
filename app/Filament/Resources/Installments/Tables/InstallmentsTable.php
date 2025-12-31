<?php

namespace App\Filament\Resources\Installments\Tables;

use App\Enums\CategoryIconEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Actions\InstallmentActions;
use App\Filament\Resources\Installments\Utilities\InstallmentSummaries;
use App\Filament\Resources\Transactions\Schemas\TransactionFormChoice;
use App\Filament\Resources\Transactions\Schemas\TransactionFormModal;
use App\Filament\Resources\Transactions\Schemas\TransactionInfolistModal;
use App\Helpers\MaskHelper;
use App\Models\Category;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Hydrat\TableLayoutToggle\Facades\TableLayoutToggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class InstallmentsTable
{
    public static function configure(Table $table): Table
    {
        /** @var \Hydrat\TableLayoutToggle\Concerns\HasToggleableTable $livewire */
        $livewire = $table->getLivewire();

        return $table
            ->columns(
                $livewire->isGridLayout()
                    ? static::getGridTableColumns()
                    : static::getListTableColumns()
            )
            ->contentGrid(
                fn () => $livewire->isListLayout()
                    ? null
                    : [
                        'md' => 2,
                        'lg' => 3,
                        'xl' => 4,
                    ]
            )
            ->paginated(true)
            ->recordTitleAttribute('Installment')
            ->filtersFormColumns(1)
            ->filters([
                Filter::make('filter')
                    ->label('')
                    ->schema([
                        Grid::make(1)
                            ->columnSpanFull()
                            ->schema([
                                Flatpickr::make('invoice_date')
                                    ->label('Período')
                                    //                                ->disableMobile(false)
                                    ->displayFormat('F / Y')
                                    ->defaultDate(Carbon::now()->startOfMonth())
                                    ->locale('pt')
                                    ->format('Y-m')
                                    ->monthPicker()
                                    ->default(Carbon::now()->startOfMonth()->format('Y-m'))
                                    ->columnSpan(1),
                                Select::make('type')
                                    ->label('Tipo')
                                    ->placeholder('Selecione')
                                    ->options(TransactionTypeEnum::class)
                                    ->columnSpan(1),
                                Select::make('source')
                                    ->label('Fonte')
                                    ->placeholder('Selecione')
                                    ->options(TransactionSourceEnum::class)
                                    ->columnSpan(1),
                                Select::make('category')
                                    ->label('Categoria')
                                    ->placeholder('Selecione')
                                    ->options(fn () => Category::get()->groupBy('type')->map->pluck('name', 'id'))
                                    ->columnSpan(1),
                            ])
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        $dates = $data['invoice_date'] ?? null;
                        $category = $data['category'] ?? null;
                        $type = $data['type'] ?? null;
                        $source = $data['source'] ?? null;


                        $query->whereHas('transaction', function (Builder $qTransaction) use ($type, $category, $source
                        ) {
                            // Aplicação Condicional
                            $qTransaction->when($type, fn ($q, $v) => $q->where('type', $v));
                            $qTransaction->when($category, fn ($q, $v) => $q->where('category_id', $v));
                            $qTransaction->when($source, fn ($q, $v) => $q->where('source', $v));
                        });


                        if ($dates) {

                            $dateString = trim($dates);

                            Carbon::setLocale('pt');

                            $startDate = Carbon::parse($dateString);

                            $endDate = $startDate->copy()->endOfMonth()->endOfDay();


                            $query->whereBetween('due_date', [
                                $startDate->copy()->startOfMonth(),
                                $endDate
                            ]);
                        }
                    })
                    ->indicateUsing(function (array $data): ?array {
                        $indicators = [];

                        if (!empty($data['invoice_date'])) {
                            $dateString = trim($data['invoice_date']);

                            Carbon::setLocale('pt_BR');

                            $carbonDate = Carbon::parse($dateString);

                            $periodoFormatado = $carbonDate->format('F \d\e Y');

                            $indicators[] = Indicator::make('Período: ' . $periodoFormatado)
                                ->removable(false);
                        }

                        if (!empty($data['category'])) {
                            $category = Category::find($data['category']);
                            $indicators[] = Indicator::make('Categoria: ' . $category->name)
                                ->removeField('category');
                        }

                        if (!empty($data['type'])) {
                            $type = TransactionTypeEnum::from($data['type']);
                            $indicators[] = Indicator::make('Tipo: ' . $type->getLabel())
                                ->removeField('type');
                        }

                        if (!empty($data['source'])) {
                            $source = TransactionSourceEnum::from($data['source']);
                            $indicators[] = Indicator::make('Fonte: ' . $source->getLabel())
                                ->removeField('source');
                        }

                        return $indicators;
                    })
            ])
            ->deferFilters(false)
            ->recordActions([
                InstallmentActions::makeViewInstallment()
            ])
            ->recordUrl(function (mixed $record): ?string {
                if ($record instanceof Invoice) {
                    return route('filament.admin.resources.invoices.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record,
                    ]);
                }

                return null;

            })
            ->toolbarActions([])
            ->headerActions([
                Action::make('create_transaction')
                    ->model(Category::class)
                    ->modalIcon(Iconoir::Plus)
                    ->modalHeading(__('custom.title.launch'))
                    ->label(__('custom.title.launch'))
                    ->icon(Iconoir::PlusCircle)
                    ->color('primary')
                    ->button()
                    ->size(Size::ExtraLarge)
                    ->modal()
                    ->modalWidth(Width::Medium)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->schema(fn (Schema $schema) => TransactionFormChoice::configure($schema)),
                TableLayoutToggle::getToggleViewTableAction(compact: true)
                    ->link(),
            ])
            ->extraAttributes(['class' => 'list-transactions-custom']);
    }

    public static function getListTableColumns(): array
    {
        return [
            TextColumn::make('due_date')
                ->label('')
                ->date('d')
                ->size(TextSize::Large)
                ->toggleable(false),
            ViewColumn::make('icon')
                ->label('')
                ->view('components.category-icon-view')
                ->viewData(function (mixed $record) {
                    $isInvoice = $record->is_invoice ?? false;
                    if ($isInvoice) {
                        $category = Category::where('icon', CategoryIconEnum::Notes)->first();
                    } else {
                        $category = $record->transaction->category;
                    }
                    return [
                        'data' => $category
                    ];
                }),
            TextColumn::make('title')
                ->label('')
                ->description(fn ($record) => $record->description ?? ''),
            TextColumn::make('source')
                ->label('')
                ->getStateUsing(function (mixed $record): HtmlString {

                    $brandName = $record->source->name ?? 'Desconhecido';

                    $imageUrl = asset('storage/' . $record->source->icon_path);

                    $renderedHtml = view('components.source-icon-view', [
                        'image'  => $imageUrl,
                        'brand'  => $brandName,
                        'source' => $record->paymentSource,
                    ])->render();

                    return new HtmlString(Blade::render($renderedHtml));
                }),
            TextColumn::make('amount_cents')
                ->label('')
                ->money('BRL')
                ->color(fn (Model $record) => $record->amount > 0 ? Color::Green : Color::Gray)
                ->getStateUsing(function (mixed $record): string {
                    return MaskHelper::covertIntToReal($record->amount);
                })
                ->summarize(InstallmentSummaries::getSummarizers()),
            TextColumn::make('status')
                ->label('')
                ->badge()
        ];
    }

    // Define the columns for the table when displayed in grid layout
    public static function getGridTableColumns(): array
    {
        return [
            Stack::make([
                TextColumn::make('due_date')
                    ->date('d')
                    ->size(TextSize::Large),
                ViewColumn::make('icon')
                    ->label('')
                    ->view('components.category-icon-view')
                    ->viewData(function (mixed $record) {
                        $isInvoice = $record->is_invoice ?? false;
                        $catIcon = $isInvoice ? CategoryIconEnum::Notes : $record->transaction->category;
                        return [
                            'data' => $catIcon
                        ];
                    }),
                //                IconColumn::make('ico')
                //                    ->label('')
                //                    ->color(function (mixed $record) {
                //                        $isInvoice = $record->is_invoice ?? false;
                //                        return $isInvoice ? Color::Gray : Color::hex($record->transaction->category->color->value);
                //                    })
                //                    ->getStateUsing(function (mixed $record) {
                //
                //                        $isInvoice = $record->is_invoice;
                //                        return $isInvoice ? CategoryIconEnum::Notes : $record->transaction->category->icon;
                //                    })
                //                    ->alignCenter()
                //                    ->extraAttributes(function (mixed $record): array {
                //                        $isInvoice = $record->is_invoice ?? false;
                //                        $colorSource = $record->transaction->category->color->value ?? "#000000";
                //
                //                        $colorHex = $isInvoice ? Color::Gray[300] : Color::generatePalette($colorSource)[200];
                //
                //                        return [
                //                            'style' => "background-color: {$colorHex}; display: inline-flex;
                //                                        align-items: center;
                //                                        justify-content: center;
                //                                        padding: 6px 9px !important;
                //                                        border-radius: 50%; /* Torna-o circular */
                //                                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra suave */
                //                                        flex-shrink: 0;
                //                                        color: #ffffff;
                //                                        width: 40px;
                //                                        height: 40px;"
                //                        ];
                //                    }),
                TextColumn::make('description')
                    ->label(''),
                TextColumn::make('source')
                    ->label('')
                    ->getStateUsing(function (mixed $record): HtmlString {

                        $brandName = $record->source->name ?? 'Desconhecido';

                        $imageUrl = asset('storage/' . $record->source->icon_path);

                        $renderedHtml = view('components.source-icon-view', [
                            'image' => $imageUrl,
                            'brand' => $brandName
                        ])->render();

                        return new HtmlString(Blade::render($renderedHtml));
                    }),
                TextColumn::make('amount_cents')
                    ->label('')
                    ->money('BRL')
                    ->color(fn (Model $record) => $record->amount > 0 ? Color::Green : Color::Gray)
                    ->getStateUsing(function (mixed $record): string {
                        return MaskHelper::covertIntToReal($record->amount);
                    })
                    ->summarize(InstallmentSummaries::getSummarizers()),
                TextColumn::make('status')
                    ->label('')
                    ->badge()
            ])
                ->
                space(3)->extraAttributes([
                    'class' => 'pb-2',
                ])
        ];
    }

}
