<?php

namespace App\Filament\Resources\Installments;

use App\Enums\CategoryIconEnum;
use App\Filament\Resources\Installments\Pages\ManageInstallments;
use App\Filament\Resources\Installments\Tables\InstallmentsTable;
use App\Helpers\MaskHelper;
use App\Livewire\CustomDataTable;
use App\Models\Installment;
use App\Models\User;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Hydrat\TableLayoutToggle\Facades\TableLayoutToggle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class InstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static string|BackedEnum|null $navigationIcon = Iconoir::DataTransferWarning;

    public static function getModelLabel(): string
    {
        return __('custom.title.releases');
    }

    public static function getNavigationLabel(): string
    {
        return __('custom.title.releases');
    }

    public static function getPluralLabel(): ?string
    {
        return __('custom.title.releases');
    }

    protected static ?int $navigationSort = 1;


    //    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Installment')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return InstallmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInstallments::route('/'),
        ];
    }

    public function viewAny(User $user): bool
    {
        return true; // Or return a condition, like $user->role_id === Role::ADMIN;
    }

    public static function canView(?Model $record): bool // 🚨 CORREÇÃO: Adicione o '?' para tornar o Model opcional
    {

        // A lógica de verificação
        return true;
    }

    public static function canEdit(?Model $record): bool // 🚨 CORREÇÃO: Adicionar o '?' 🚨
    {

        // Lógica de verificação do Tenant/Família
        return true;
    }


    public static function getListTableColumns(): array
    {
        return [
            IconColumn::make('ico')
                ->color(function (mixed $record) {
                    $isInvoice = $record->is_invoice ?? false;
                    return $isInvoice ? Color::Gray : Color::hex($record->transaction->category->color->value);
                })
                ->getStateUsing(function (mixed $record) {
                    $isInvoice = $record->is_invoice ?? false;
                    return $isInvoice ? CategoryIconEnum::Notes : $record->category->icon;
                })
                ->alignCenter()
                ->extraAttributes(function (mixed $record): array {

                    $isInvoice = $record->is_invoice ?? false;

                    $colorSource = $record->transaction->category->color->value ?? "#000000";

                    // 3. Converte a fonte da cor para uma string HEX final (Se for um objeto Color)
                    $colorHex = $isInvoice ? Color::Gray[300] : Color::generatePalette($colorSource)[200];

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

                    return MaskHelper::covertIntToReal($record->amount);
                }),
        ];
    }

    // Define the columns for the table when displayed in grid layout
    public static function getGridTableColumns(): array
    {
        return [
            Stack::make([
                IconColumn::make('ico')
                    ->color(function (mixed $record) {
                        $isInvoice = $record->is_invoice ?? false;
                        return $isInvoice ? Color::Gray : Color::hex($record->transaction->category->color->value);
                    })
                    ->getStateUsing(function (mixed $record) {
                        $isInvoice = $record->is_invoice ?? false;
                        return $isInvoice ? CategoryIconEnum::Notes : $record->category->icon;
                    })
                    ->alignCenter()
                    ->extraAttributes(function (mixed $record): array {

                        $isInvoice = $record->is_invoice ?? false;

                        $colorSource = $record->transaction->category->color->value ?? "#000000";

                        // 3. Converte a fonte da cor para uma string HEX final (Se for um objeto Color)
                        $colorHex = $isInvoice ? Color::Gray[300] : Color::generatePalette($colorSource)[200];

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

                        return MaskHelper::covertIntToReal($record->amount);
                    }),
            ])
                ->
                space(3)->extraAttributes([
                    'class' => 'pb-2',
                ])
        ];
    }

}
