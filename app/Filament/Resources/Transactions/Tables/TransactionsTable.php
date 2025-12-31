<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\CategoryIconEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Actions\SimpleActions;
use App\Filament\Resources\Transactions\Schemas\TransactionFormChoice;
use App\Filament\Resources\Transactions\Schemas\TransactionFormModal;
use App\Filament\Resources\Transactions\Schemas\TransactionInfolistModal;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Helpers\MaskHelper;
use App\Models\Category;
use App\Models\Installment;
use App\Models\Invoice;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class TransactionsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->view('components.custom-icon-view')
                    ->viewData(function (mixed $record) {
                        $isInvoice = $record->is_invoice ?? false;
                        $iconValue = $isInvoice
                            ? CategoryIconEnum::Notes
                            : $record->category->icon;

                        $colorValue = $isInvoice
                            ? Color::Gray
                            : ($record->category->color ?? '#A8A8A8');

                        return [
                            'icon_value' => $iconValue,
                            'color'      => $colorValue,
                        ];
                    }),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(30),
                TextColumn::make('source')
                    ->label('Fonte')
                    ->getStateUsing(function (mixed $record): HtmlString {

                        $brandName = $record->source->name ?? 'Desconhecido';

                        $imageUrl = asset('storage/' . $record->source->icon_path);

                        $renderedHtml = view('components.source-icon-view', [
                            'image' => $imageUrl,
                            'brand' => $brandName,
                        ])->render();

                        return new HtmlString(Blade::render($renderedHtml));
                    }),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->getStateUsing(function (mixed $record): string {
                        $calc = $record->type == TransactionTypeEnum::EXPENSE ? -1 : 1;
                        return MaskHelper::covertIntToReal($record->amount * $calc);
                    }),
            ])
            ->deferFilters(false)
            ->recordUrl(function (mixed $record): ?string {
                if ($record instanceof Invoice) {
                    return route('filament.admin.resources.transactions.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record,
                    ]);
                }

                return null;

            })
            ->recordAction('view-modal')
            ->filters([
                Filter::make('filter')
                    ->label('')
                    ->schema([
                        Flex::make([
                            Flatpickr::make('invoice_date')
                                ->label('Período')
                                //                                ->disableMobile(false)
                                ->displayFormat('F / Y')
                                ->defaultDate(Carbon::now()->startOfMonth())
                                ->locale('pt')
                                ->format('Y-m')
                                ->monthPicker()
                                ->default(Carbon::now()->startOfMonth()->format('Y-m')),
                            Select::make('type')
                                ->label('Tipo')
                                ->placeholder('Selecione')
                                ->options(TransactionTypeEnum::class),
                            Select::make('source')
                                ->label('Fonte')
                                ->placeholder('Selecione')
                                ->options(TransactionSourceEnum::class),
                            Select::make('category')
                                ->label('Categoria')
                                ->placeholder('Selecione')
                                ->options(fn () => Category::get()->groupBy('type')->map->pluck('name', 'id')),
                        ])
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        $dates = $data['invoice_date'] ?? null;
                        $category = $data['category'] ?? null;
                        $type = $data['type'] ?? null;
                        $source = $data['source'] ?? null;

                        $query->when($category, fn ($q) => $q->where('category_id', $category));
                        $query->when($type, fn ($q) => $q->where('type', $type));
                        $query->when($source, fn ($q) => $q->where('source', $source));


                        if ($dates) {

                            $dateString = trim($dates);

                            Carbon::setLocale('pt');

                            $startDate = Carbon::parse($dateString);

                            $endDate = $startDate->copy()->endOfMonth()->endOfDay();


                            $query->whereBetween('due_date', [
                                $startDate,
                                $endDate
                            ]);
                        }
                    })
                    ->indicateUsing(function (array $data): ?array {
                        $indicators = [];

                        if (!empty($data['invoice_date'])) {
                            $dateString = trim($data['invoice_date']);

                            Carbon::setLocale('pt');

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
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('view-modal')
                    ->model(Installment::class)
                    ->slideOver()
                    ->modalHeading("Teste")
                    ->modalWidth(Width::Small)
                    ->modalIcon(Heroicon::InformationCircle)
                    ->schema(fn (Schema $schema) => TransactionInfolistModal::configure($schema))
                    ->modalFooterActions(function (Installment $record) {
                        $actions[] = EditAction::make('edit-modal')
                            ->modalHeading("Editar")
                            ->model(Installment::class)
                            ->modalWidth(Width::Small)
                            ->modalIcon(Heroicon::PencilSquare)
                            ->color('primary')
                            // Preenche o formulário com os atributos do registro
                            //            ->fillForm(fn (Model $record) => $record->toArray())
                            // Usa o schema fornecido pela closure
                            ->schema(fn (Schema $schema) => TransactionFormModal::configure($schema));

                        // Ação de Exclusão (Se for um item específico da família)
                        $actions[] = DeleteAction::make()
                            ->modalHeading(fn (Installment $record) => "Excluir");


                        return $actions;
                    })->
                    visible(fn (mixed $record) => $record instanceof \App\Models\Installment)


                //                SimpleActions::getViewWithEditAndDelete(
                //                    width         : Width::Small,
                //                    schemaCallback: fn (Schema $schema) => TransactionInfolistModal::configure($schema),
                //                    actionCallback: function (array $data, Installment $record, Action $action): void {
                //
                //                    },
                //                    model         : Installment::class,
                //                    recordName    : 'Conta',
                //                    recordAction  : fn (mixed $record): bool => $record instanceof Installment,
                //                    modal         : false
                //                )
                //                    ->authorize(true)
                //                                ->hidden(fn (mixed $record): bool => $record instanceof \App\Models\Invoice),
            ])
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
                    ->schema(fn (Schema $schema) => TransactionFormChoice::configure($schema))
            ])
            ->extraAttributes([
                'class' => 'list-transactions-custom'
            ]);
    }
}
