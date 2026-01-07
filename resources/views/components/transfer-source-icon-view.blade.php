@php
    // Dados da Origem
    use Filafly\Icons\Iconoir\Enums\Iconoir;
    use Filament\Support\Colors\Color;
    use Filament\Support\Enums\IconSize;

    $imageOrigin = asset('storage/' . ($origin->brand->icon_path ?? ''));
    $nameOrigin = $origin->name ?? 'Origem';

    // Dados do Destino
    $imageDestine = asset('storage/' . ($destine->brand->icon_path ?? ''));
    $nameDestine = $destine->name ?? 'Destino';

    $arrOrigin = [
        'icon' => $imageOrigin,
        'name' => $nameOrigin
    ];

    $arrDestine = [
        'icon' => $imageDestine,
        'name' => $nameDestine
    ];

    $icon = Iconoir::SendDollars->getIconForSize(IconSize::Medium);

    $arrowColor = Color::Red[500];
    if(!$isOut){
        $arrOrigin = [
            'icon' => $imageDestine,
            'name' => $nameDestine
        ];

        $arrDestine = [
            'icon' => $imageOrigin,
            'name' => $nameOrigin
        ];

        $icon = Iconoir::ReceiveDollars->getIconForSize(IconSize::Medium);

        $arrowColor = Color::Green[500];
    }

    // Definição de cores da seta baseada no fluxo
//    $arrowColor = $isOut ? 'text-danger-500' : 'text-success-500';

    $transferContainer = "display: inline-flex; align-items: center; gap: 8px; padding: 4px 0; width:100%; min-width: 150px;";

    $bankItem = "display: flex; flex-direction: column; align-items: center; gap: 4px; width: 40%;";

    $containerImage = "width: 35px; height: 35px; border-radius: 50%; background-position: center; background-repeat: no-repeat; background-size: cover; border: 1px solid transparent; outline: 1px solid #e1e1e1; background-color: #fff; background-attachment: local;background-clip: content-box;";

    $bankLabel = "font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center;";

    $transferArrow = "display: flex; align-items: center; justify-content: center; padding-bottom: 14px; width:20%;";
@endphp

<div class="transfer-container" style="{{$transferContainer}}}">
    <div class="bank-item" style="{{$bankItem}}">
        <div class="container-image icon-account"
             style="background-image: url('{{ $arrOrigin['icon'] }}');{{$containerImage}}"></div>
        <span class="bank-label" style="{{$bankLabel}}">{{ $arrOrigin['name'] }}</span>
    </div>

    <div class="transfer-arrow" style="{{$transferArrow}}">
        <x-filament::icon
            icon="{{$icon}}"
            color="{{ $arrowColor }}"
            class="w-5 h-5"
            style="width: 20px; height: 20px;"
        />
    </div>

    <div class="bank-item" style="{{$bankItem}}">
        <div class="container-image icon-account"
             style="background-image: url('{{ $arrDestine['icon'] }}');{{$containerImage}}"></div>
        <span class="bank-label" style="{{$bankLabel}}">{{ $arrDestine['name'] }}</span>
    </div>
</div>
