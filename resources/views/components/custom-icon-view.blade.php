@php
    use App\Enums\CategoryColorPaletteEnum;use App\Enums\CategoryIconEnum;
    use Filafly\Icons\Iconoir\Enums\Iconoir;use \Filament\Support\Colors\Color;use Filament\Support\Enums\IconSize;

    $iconName = $iconInstance = Iconoir::from($icon_value->value)->getIconForSize(IconSize::Large);

    $bgColor = $color?->value ?? $color[500]

@endphp

<span
    title="{{ $icon_value->value }}"
    class="fi-ta-text-item fi-ta-text-item-custom flex fi-align-center"
    style="background-color: {{ $bgColor }};"
>
    <x-filament::icon
        icon="{{$iconName}}"
        color="{{$bgColor}}"
        class="
            h-8
            text-white
            dark:text-gray-900
        "
    />

</span>

<style>
    /* Estilo customizado para o componente */
    .fi-ta-text-item.fi-ta-text-item-custom {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 9px !important;
        border-radius: 50%; /* Torna-o circular */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra suave */
        flex-shrink: 0;
        color: #ffffff;
    }

    .fi-ta-text-item.fi-ta-text-item-custom svg {

        color: #ffffff;
    }
</style>
