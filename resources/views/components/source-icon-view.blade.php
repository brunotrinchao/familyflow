@php
    $imageUrl = $image;
    $brandName = $brand;
    $dataSource = $souce ?? null;
    $classSource = $dataSource == \App\Enums\TransactionSourceEnum::ACCOUNT ? 'icon-account' :  '';
//    dd($record->source == TransactionSourceEnum::CREDIT_CARD );
@endphp

<span
    title=""
    class="fi-ta-text-item fi-ta-text-item-resource"
>
    <span class="container-image {{$classSource}}" style="background-image: url('{{$imageUrl}}')"></span>
    <span class="ml-2">{{$brandName}}</span>

</span>

<style>
    /* Estilo customizado para o componente */
    .fi-ta-text-item.fi-ta-text-item-resource {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 9px !important;
        flex-shrink: 0;
    }

    /*.fi-ta-text-item.fi-ta-text-item-resource .image_grand {*/
    /*    width: 50px;*/
    /*    height: 35px;*/
    /*    background-position: center;*/
    /*    background-repeat: no-repeat;*/
    /*    background-size: cover;*/
    /*}*/
.fi-ta-text-item.fi-ta-text-item-resource .container-image{
    width: 60px;
    height: 35px;
        border-radius: 5px;
    overflow: hidden;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
}

.fi-ta-text-item.fi-ta-text-item-resource .container-image.icon-account{

    border-radius: 50%;
    width: 35px;
}
    .fi-ta-text-item.fi-ta-text-item-resource img {
        border-radius: 5px;
        width: 100%;
    }
</style>
