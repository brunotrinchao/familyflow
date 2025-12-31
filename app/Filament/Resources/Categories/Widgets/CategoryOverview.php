<?php

namespace App\Filament\Resources\Categories\Widgets;

use App\Enums\CategoryTypeEnum;
use App\Models\Category;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CategoryOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // 1. Obter a contagem total de todas as categorias em uma única consulta.
        $totalCount = Category::count();

        // 2. Obter as contagens agrupadas por 'type' em uma única consulta.
        // Usamos selectRaw e pluck para obter um array associativo ['type' => count].
        $typeCounts = Category::query()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->all();

        // 3. Função auxiliar para obter a contagem por tipo (evita erros se o tipo não existir no resultado).
        $getCount = fn (CategoryTypeEnum $type): int => $typeCounts[$type->value] ?? 0;

        return [
            // Total
            Stat::make('Total', $totalCount),

            // Despesas (Expense)
            Stat::make(
                CategoryTypeEnum::EXPENSE->getLabel(),
                $getCount(CategoryTypeEnum::EXPENSE)
            ),

            // Receitas (Income)
            Stat::make(
                CategoryTypeEnum::INCOME->getLabel(),
                $getCount(CategoryTypeEnum::INCOME)
            ),
        ];
    }
}
