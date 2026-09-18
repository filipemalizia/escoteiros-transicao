<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Portal do Jovem' }} - Escoteiros</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-white">
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4 flex items-center gap-3">
            <a
                href="{{ $voltarPara ?? route('portal.progresso') }}"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-white/5"
            >
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-5 w-5" />
            </a>
            @if (isset($logoUrl))
                <img src="{{ $logoUrl }}" alt="" class="h-9 w-9 shrink-0 rounded-lg object-contain">
            @else
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-8 w-8 shrink-0">
                <div class="hidden sm:block">
                    @include('filament.components.brand-nome')
                </div>
            @endif
            <h1 class="text-lg font-bold text-gray-950 dark:text-white">{{ $title ?? 'Portal do Jovem' }}</h1>
        </div>

        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
