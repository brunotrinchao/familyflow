<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionTypeEnum;
use App\Services\DashboardWidgetService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DashboardChartCategoryExpense extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * ID único do gráfico.
     */
    protected static ?string $chartId = 'categoryExpenseChart';

    /**
     * Ordem de exibição.
     */
    protected static ?int $sort = 5;

    /**
     * Largura das colunas.
     */
    protected int|string|array $columnSpan = [
        'default' => 12,
        'md'      => 6,
        'lg'      => 4,
    ];

    /**
     * Altura do conteúdo.
     */
//    protected static ?int $contentHeight = 350;

    /**
     * Cabeçalho do widget.
     */
    //    protected static ?string $heading = 'Despesas por Categoria';

    /**
     * Lazy loading.
     */
    protected static bool $deferLoading = true;

    /**
     * Configurações do gráfico ApexCharts.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $service = app(DashboardWidgetService::class);
        $categoryData = $service->getDataCategory(
            $this->filters ?? [],
            TransactionTypeEnum::EXPENSE
        );

        if ($categoryData->isEmpty()) {
            return $this->getEmptyChartOptions();
        }

        $labels = [];
        $data = [];
        $total = 0;

        foreach ($categoryData as $categoryName => $details) {
            $labels[] = $categoryName;
            $data[] = $details['total'] / 100; // Converter para reais
            $total += $details['total'];
        }


        return $service->getScriptDonutPie($total, 'Despesas por Categoria', 'categorias no periodo', $data, $labels, data:$categoryData->toArray());
    }

    /**
     * Retorna opções para gráfico vazio.
     *
     * @return array
     */
    private function getEmptyChartOptions(): array
    {
        return [
            'chart'      => [
                'type'   => 'donut',
                'height' => 300,
            ],
            'series'     => [1],
            'labels'     => ['Sem dados'],
            'colors'     => ['#e5e7eb'],
            'legend'     => [
                'show' => false,
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }

       protected function extraJsOptions(): ?\Filament\Support\RawJs
    {
        return app(DashboardWidgetService::class)->getExtraScriptStats();
    }

    /**
     * Obtém a descrição do widget.
     *
     * @return string|null
     */
    protected function getDescription(): ?string
    {
        $service = app(DashboardWidgetService::class);
        $categoryData = $service->getDataCategory(
            $this->filters ?? [],
            TransactionTypeEnum::EXPENSE
        );

        if ($categoryData->isEmpty()) {
            return 'Nenhuma despesa no período';
        }

        $totalCategories = $categoryData->count();
        return "{$totalCategories} categorias com despesas";
    }
}
