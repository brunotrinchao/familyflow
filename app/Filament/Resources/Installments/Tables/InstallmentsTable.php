<?php

namespace App\Filament\Resources\Installments\Tables;

use App\Enums\CategoryIconEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Actions\InstallmentActions;
use App\Filament\Resources\Installments\Schemas\InstallmentTable;
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

                            $carbonDate = Carbon::parse($dateString);

                            $periodoFormatado = $carbonDate->translatedFormat('F \d\e Y');

                            $indicators[] = Indicator::make('Período: ' . mb_ucfirst($periodoFormatado))
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
        return InstallmentTable::configure();
    }

    // Define the columns for the table when displayed in grid layout
    public static function getGridTableColumns(): array
    {
        return [
            Stack::make(InstallmentTable::configure())
                ->
                space(3)->extraAttributes([
                    'class' => 'pb-2',
                ])
        ];
    }

}
