@php
    $align ??= 'left';
    $extraClass ??= '';
@endphp

<div class="flex flex-col leading-tight {{ $align === 'center' ? 'items-center text-center' : '' }} {{ $extraClass }}">
    <span class="text-sm font-semibold text-gray-950 dark:text-white">Ferramenta de Transição</span>
    <span class="text-xs text-gray-500 dark:text-gray-400">GEMar Marcílio Dias - 02BA</span>
</div>
