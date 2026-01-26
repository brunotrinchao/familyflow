<?php

namespace App\Filament\Widgets;

use App\Services\DashboardWidgetService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DashboardChartInvoice extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * ID único do gráfico.
     */
    protected static ?string $chartId = 'invoiceChart';

    /**
     * Ordem de exibição.
     */
    protected static ?int $sort = 4;

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
    //    protected static ?string $heading = 'Faturas por Cartão';

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
        $invoiceData = $service->getDataInvoice($this->filters ?? []);

        if ($invoiceData->isEmpty()) {
            return $this->getEmptyChartOptions();
        }

        $labels = [];
        $data = [];
        $total = 0;
        foreach ($invoiceData as $creditCardName => $details) {
            $labels[] = $creditCardName;
            $data[] = $details['total'] / 100; // Converter para reais
            $total += $details['total'];
        }

        return $service->getScriptDonutPie(totalItems: $total, title: 'Faturas', subtitle: 'faturas no periodo', series: $data, labels: $labels, data: $invoiceData->toArray());
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

}
