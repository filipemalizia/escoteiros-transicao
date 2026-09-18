@props(['concluido' => false, 'solicitado' => false])

@php
    $icone = match (true) {
        $concluido => 'heroicon-s-check-circle',
        $solicitado => 'heroicon-o-clock',
        default => 'heroicon-o-check-circle',
    };

    $cor = match (true) {
        $concluido => 'text-green-600 dark:text-green-400',
        $solicitado => 'text-amber-500 dark:text-amber-400',
        default => 'text-gray-300 dark:text-gray-600',
    };
@endphp

<x-filament::icon :icon="$icone" {{ $attributes->class(['h-5 w-5 shrink-0', $cor]) }} />
