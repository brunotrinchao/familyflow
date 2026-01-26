<?php

namespace App\Filament\Pages;

use App\Enums\Icon\Ionicons;
use App\Filament\Widgets\_oldDashboardStatsOverview;
use App\Filament\Widgets\DashboardChartBalanceComparison;
use App\Filament\Widgets\DashboardChartCategoryExpense;
use App\Filament\Widgets\DashboardChartCategoryIncome;
use App\Filament\Widgets\DashboardChartInvoice;
use App\Filament\Widgets\DashboardTableRecentTransactions;
use App\Filament\Widgets\DashboardWidgetDailyPerSource;
use App\Filament\Widgets\Stats\DashboardStatsBalanceOverview;
use App\Filament\Widgets\Stats\DashboardStatsExpenseOverview;
use App\Filament\Widgets\Stats\DashboardStatsIncomeOverview;
use Carbon\Carbon;
use Coolsam\Flatpickr\Enums\FlatpickrPosition;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|null|\BackedEnum $navigationIcon = Iconoir::DashboardSpeed;
    //    protected string $view = 'filament.pages.dashboard';

    protected ?string $subheading = "Visão geral das suas finanças";

//    public function getSubheading(): ?string
//    {
//        $period = $this->filters['due_date'] ? Carbon::parse($this->filters['due_date']) : now();
//        return 'Análise do período: ' . $period->locale('pt')->format('F/Y');
//    }

    public function getColumns(): int| array
    {
        return 12;
    }

     public function getFiltersFormWidth(): ?string
    {
        return '2xl'; // xs, sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
    }

    public function getFiltersFormColumns(): int | string | array
    {
        return 1;
    }

    public function mount(): void
    {
        //
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make([
                    'default' => 12,
                ])
                    ->schema([
                        Flatpickr::make('due_date')
                            ->hiddenLabel()
                            ->displayFormat('F / Y')
                            ->defaultDate(Carbon::now()->startOfMonth())
                            ->locale('pt')
                            ->format('Y-m')
                            ->monthPicker()
                            ->position(FlatpickrPosition::AUTO_CENTER)
                            ->default(Carbon::now()->startOfMonth()->format('Y-m'))
                            // Ocupa 4 colunas e pula as 4 primeiras (ficando no centro: 4 + 4 + 4 = 12)
                            ->columnStart(5)
                            ->columnSpan(3)
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            _oldDashboardStatsOverview::class,
            // Linha 1: Stats Overview (12 cols)
//            DashboardStatsIncomeOverview::class,
//            DashboardStatsExpenseOverview::class,
//            DashboardStatsBalanceOverview::class,

            // Linha 2: Tabela (8 cols) + Faturas (4 cols)
            DashboardTableRecentTransactions::class,
            DashboardChartInvoice::class,

            // Linha 3: Gastos Diários (8 cols) + Despesas (4 cols)
            DashboardWidgetDailyPerSource::class,
            DashboardChartCategoryExpense::class,

            // Linha 5: Receitas vs Despesas (4 cols direita)
            DashboardChartBalanceComparison::class,

            // Linha 4: Receitas por Categoria (4 cols direita)
            DashboardChartCategoryIncome::class,

        ];
    }
}
