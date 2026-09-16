@props(['color' => 'gray'])

{{--
    Badge com Tailwind puro (não usa <x-filament::badge>) porque este
    componente é reaproveitado no portal público, que não carrega o CSS
    compilado do painel Filament (só o Tailwind "cru" de resources/css/app.css)
    — usa cores base do Tailwind em vez dos tokens semânticos do Filament
    (success/warning/danger/info), que só existem dentro do tema do painel.
--}}
@php
    $classes = match ($color) {
        'success' => 'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-400/10 dark:text-red-400',
        'info' => 'bg-sky-50 text-sky-700 dark:bg-sky-400/10 dark:text-sky-400',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-400/10 dark:text-gray-300',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 whitespace-nowrap rounded-md px-2 py-1 text-xs font-medium', $classes]) }}>
    {{ $slot }}
</span>
