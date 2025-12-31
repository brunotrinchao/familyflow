<x-filament-widgets::widget>
    @php
        $data = $this->getNavigationData();
        $previous = $data['previous'];
        $next = $data['next'];
        $color = \Filament\Support\Colors\Color::Blue[100];
    @endphp

    <div class="flex justify-between align-middle w-full gap-2">

        @if ($previous)
            <x-filament::button
                tag="a"
                href="{{ $previous['url'] }}"
                icon="iconoir-nav-arrow-left"
                color="gray"
                outlined
                tooltip="{{$previous['label'] }}"
                class="justify-center rounded-full"
                style="padding: calc(var(--spacing) * 3);"
            >
{{--                {{ $previous['label'] }}--}}
            </x-filament::button>
        @else
            <div class="flex-1"></div>
        @endif
        @if ($next)
            <x-filament::button
                tag="a"
                href="{{ $next['url'] }}"
                icon="iconoir-nav-arrow-right"
                icon-position="after"
                tooltip="{{$next['label'] }}"
                color="gray"
                outlined
                class="justify-center  rounded-full"
                style="padding: calc(var(--spacing) * 3);"
            >
{{--                {{ $next['label'] }}--}}
            </x-filament::button>
        @else
            <div class="flex-1"></div>
        @endif
    </div>
</x-filament-widgets::widget>
