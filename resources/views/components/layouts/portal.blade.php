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
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
