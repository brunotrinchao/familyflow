@php
    use Filament\Support\Colors\Color;

    if (!$data) {
        return;
    }

    $hexColor = Color::hex($data->color->value ?? $data->color);

    // O ícone pode ser passado como a string do valor do Enum
    $icon = $data->icon->getIcon() ?? $data->icon;

    $showLabel = $showLabel ?? false;
@endphp

<span
    title=""
    class="flex justify-between align-middle items-center h-8"
>
<span
    title="{{ $data->name ?? '' }}"
    class="flex items-center justify-center w-8 h-8 rounded-full shadow-sm"
    style="background-color: {{ $hexColor[100]}}"
>
    <x-filament::icon
        :icon="$icon"
        class="w-5 h-5 "
        style="color: {{ $hexColor[500]}} !important;"
    />
</span>
</span>


@if($showLabel)
    <span class="ml-2 flex justify-between align-middle items-center font-light">{{$data->name}}</span>
@endif

<style>
    /* Estilo customizado para o componente */
    .fi-ta-text-item.fi-ta-text-item-custom {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px;
        border-radius: 50%; /* Torna-o circular */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra suave */
        flex-shrink: 0;
        color: #ffffff;
    }

    .fi-ta-text-item.fi-ta-text-item-custom svg {

        color: #ffffff;
    }
</style>
