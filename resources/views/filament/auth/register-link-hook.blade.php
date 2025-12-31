@php
    // Obtém a classe de registro definida no painel
    $registrationClass = filament()->getRegistrationPage();
    $registrationUrl = $registrationClass::getUrl();
@endphp

@if ($registrationClass && $registrationUrl)
    <p class="text-sm text-center text-gray-600 dark:text-gray-400 mt-4">
        Não tem uma conta?
        {{-- O getTitle() usará o nome da sua classe (CustomRegister) ou o título padrão do Register --}}
        <a href="{{ $registrationUrl }}" class="font-medium text-primary-600 hover:text-primary-500" wire:navigate>
            {{ $registrationClass::getTitle() }}
        </a>
    </p>
@endif
