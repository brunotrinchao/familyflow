<?php

namespace App\View\Components;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CategoryIconView extends Component
{
//    public Category $record;
//    public string $iconName;
//    public string $iconColor;
//
//    /**
//     * @param Category $record O registro da categoria que está sendo renderizado.
//     */
//    public function __construct(Category $record) // Injete o objeto Category
//    {
//        $this->record = $record;
//
//        // 1. Defina as propriedades para acesso simplificado na view
//        // Assumindo que Category tem colunas 'icon' e 'color'
//        $this->iconName = $record->icon;
//        $this->iconColor = $record->color;
//    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.category-icon-view');
    }
}
