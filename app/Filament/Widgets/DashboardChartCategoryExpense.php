<?php

namespace App\Filament\Widgets;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Installment;
use App\Services\DashboardWidgetService;
use Carbon\Carbon;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;

class DashboardChartCategoryExpense extends ChartWidget
{
    use InteractsWithPageFilters;

    private Collection $dataCategory;

    private string $title = 'Despesas por categoria';

//    public function getHeading(): string|null|\Illuminate\Contracts\Support\Htmlable
//    {
//        return 'Despesas por categoria';
//    }

    protected int|string|array $columnSpan = [
        'default' => 12,
        'lg'      => 4
    ];

    protected static bool $isLazy = true;

//    protected ?string $maxHeight = '400px';

    protected function getData(): array
    {
        $service = app(DashboardWidgetService::class);
       $this->dataCategory = $service->getDataCategory($this->filters ?? [], TransactionTypeEnum::EXPENSE);
        $labels = [];
        $data = [];
        foreach ($this->dataCategory as $categoryName => $items) {
            $labels[] = $categoryName;
            $data[] = $items->sum('amount');
        }

        $fixedColors = $service->colors;

        return [
            'datasets' => [
                [
                    'label'           => 'Categoria',
                    'data'            => $data,
                    'backgroundColor' => $fixedColors,
                    'hoverOffset'     => 4
                ]
            ],
            'labels'   => $labels
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): RawJs
    {
        return app(DashboardWidgetService::class)->getScriptDonnet($this->title);
    }
}
