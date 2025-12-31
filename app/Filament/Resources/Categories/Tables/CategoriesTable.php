<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Filament\Actions\SimpleActions;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Brand;
use App\Models\Category;
use App\Services\CategoryService;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Hydrat\TableLayoutToggle\Facades\TableLayoutToggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CategoriesTable
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
            ->recordClasses(fn (Category $record) => $record->family_id == null ? 'category-row-default' : null)
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('family_id', 'desc')->orderBy('name', );
            })
            ->contentGrid(
                fn () => $livewire->isListLayout()
                    ? null
                    : [
                        'md' => 2,
                        'lg' => 3,
                        'xl' => 4,
                    ]
            )
            ->recordTitleAttribute('Categoria')
            ->filters([])

            ->recordUrl(null)
            ->recordAction('edit-modal')
            ->recordActions([
                SimpleActions::getViewWithEditAndDelete(
                    width         : Width::FiveExtraLarge,
                    schemaCallback: fn (Schema $schema) => CategoryForm::configure($schema),
                    actionCallback: function (array $data, Category $record, Action $action): void {
                        CategoryService::update($data, $record, $action);
                    },
                    recordName    : 'Categoria',
                    recordAction  : fn (Category $record): bool => $record->family_id !== null,
                )
                    ->visible(fn (Category $record): bool => $record->family_id !== null),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('createCategory')
                    ->icon(Iconoir::PlusCircle)
                    ->label('Categoria')
                    ->size(Size::ExtraLarge)
                    ->button()
                    ->color('primary')
                    ->modalIcon(Iconoir::Plus)
                    ->modalSubmitActionLabel('Cadastrar')
                    ->schema(fn (Schema $schema) => CategoryForm::configure($schema))
                    ->action(function (array $data, \Filament\Actions\Action $action): void {
                        CategoryService::create($data, $action);
                    }),
                TableLayoutToggle::getToggleViewTableAction(compact: true)
                ->link(),
            ])
            ->reorderableColumns(false);
    }

    public static function getListTableColumns(): array
    {
        return [
            IconColumn::make('ico')
                ->label('')
                    ->color(function (mixed $record) {
                        return Color::hex($record->color->value);
                    })
                    ->getStateUsing(function (mixed $record) {
                        return $record->icon;
                    })
                    ->extraAttributes(function (mixed $record): array {
                        $colorSource = $record->color->value;

                        $colorHex = Color::generatePalette($colorSource)[200];

                        return [
                            'style' => "background-color: {$colorHex}; display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        padding: 6px 9px !important;
                                        border-radius: 50%; /* Torna-o circular */
                                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra suave */
                                        flex-shrink: 0;
                                        color: #ffffff;
                                        width: 40px;
                                        height: 40px;"
                        ];
                    }),
            TextColumn::make('name')
                ->label(''),
            TextColumn::make('type')
                ->label('')
                ->badge(),
            TextColumn::make('default_status')
                ->label('')
                ->badge()
                ->size(TextSize::Small)
                ->color('#ffffff')
                ->getStateUsing(fn (Category $record): string => $record->family_id === null ? 'Padrão' : ''
                )
        ];
    }

    // Define the columns for the table when displayed in grid layout
    public static function getGridTableColumns(): array
    {
        return [
            Stack::make([
                ViewColumn::make('icon')
                    ->label('')
                    ->view('components.category-icon-view')
                    ->viewData(fn (Category $category) => ['data' => $category])
                    ->alignCenter(),
                TextColumn::make('name')
                    ->label('')
                    ->alignCenter(),
                TextColumn::make('type')
                    ->label('')
                    ->badge()
                    ->alignCenter(),
                TextColumn::make('default_status')
                    ->label('')
                    ->badge()
                    ->size(TextSize::Small)
                    ->alignCenter()
                    ->color('#ffffff')
                    ->getStateUsing(fn (Category $record): string => $record->family_id === null ? 'Padrão' : ''
                    )
            ])
                ->
                space(3)->extraAttributes([
                    'class' => 'pb-2',
                ])
        ];
    }
}
