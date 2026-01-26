<?php

namespace App\Filament\Widgets\Stats;

use App\Enums\TransactionTypeEnum;
use App\Services\DashboardWidgetService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DashboardStatsIncomeOverview extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * ID único do gráfico.
     */
    protected static ?string $chartId = 'dashboardIncomeStats';

    /**
     * Ordem de exibição.
     */
    protected static ?int $sort = 1;

    /**
     * Largura das colunas.
     */
    protected int|string|array $columnSpan = [
        'default' => 12,
        'sm'      => 6,
        'md'      => 4,
        'lg'      => 4,
    ];

    /**
     * Altura do conteúdo.
     */
//    protected static ?int $contentHeight = 150;

    /**
     * Cabeçalho do widget.
     */
//    protected static ?string $heading = 'Receitas';

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
        $stats = $service->getSummaryStats($this->filters ?? [], TransactionTypeEnum::INCOME);

        return [
            'chart' => [
                'type' => 'area',
                'height' => 100,
                'sparkline' => [
                    'enabled' => true,
                ],
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Receitas',
                    'data' => $stats['history'],
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'fill' => [
                'opacity' => 1,
            ],
            'colors' => ['#10b981'], // Verde (emerald-500)
            'labels' => $stats['labels'],
            'xaxis' => [
                'type' => 'category',
                'labels' => [
                    'show' => false,
                ],
                'axisBorder' => [
                    'show' => false,
                ],
                'axisTicks' => [
                    'show' => false,
                ],
            ],
            'yaxis' => [
                'show' => false,
            ],
            'grid' => [
                'show' => false,
                'padding' => [
                    'top' => -20,
                    'right' => 0,
                    'bottom' => -8,
                    'left' => 0,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'title'    => [
                'text'  => $stats['total_formatted'],
                'style' => [
                    'fontSize'   => '20px',
                    'fontWeight' => 'bold'
                ],
            ],
            'subtitle' => [
                'text' => 'Receitas do Período',
            ],
        ];
    }

        protected function extraJsOptions(): ?\Filament\Support\RawJs
        {
        return app(DashboardWidgetService::class)->getExtraScriptStats();
    }

    /**
     * Obtém o cabeçalho com valor total.
     *
     * @return string
     */
//    protected function getHeading(): string
//    {
//        $service = app(DashboardWidgetService::class);
//        $stats = $service->getSummaryStats($this->filters ?? []);
//
//        return $stats['total_income_formatted'];
//    }

    /**
     * Obtém a descrição do widget.
     *
     * @return string|null
     */
    protected function getDescription(): ?string
    {
        $service = app(DashboardWidgetService::class);
        $stats = $service->getSummaryStats($this->filters ?? []);

        return "{$stats['income_count']} receitas no período";
    }

    /**
     * Footer com informação adicional.
     *
     * @return string|null
     */
//    protected function getFooter(): ?string
//    {
//        return 'Receitas lançadas e pagas';
//    }
}
