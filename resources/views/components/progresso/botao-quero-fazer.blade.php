@props(['marcado' => false, 'wireClick', 'confirm' => null, 'exigeConfirmacao' => false, 'pedido' => false])

@php
    $titulo = match (true) {
        $pedido => 'Pedido enviado ao chefe. Toque para cancelar o pedido',
        $exigeConfirmacao => 'Pedir ao chefe para remover da lista de quero fazer',
        $marcado => 'Remover da lista de quero fazer',
        default => "Marcar como 'quero fazer'",
    };
@endphp

<button
    type="button"
    wire:click.stop="{{ $wireClick }}"
    @if ($confirm) wire:confirm="{{ $confirm }}" @endif
    title="{{ $titulo }}"
    {{ $attributes->class(['shrink-0 flex flex-col items-center']) }}
>
    <x-filament::icon
        :icon="$marcado ? 'heroicon-s-star' : 'heroicon-o-star'"
        class="h-5 w-5 {{ $marcado ? 'text-amber-500' : 'text-gray-300 hover:text-gray-400 dark:text-gray-600 dark:hover:text-gray-500' }}"
    />
    @if ($pedido)
        <span class="text-[10px] leading-tight text-amber-600 dark:text-amber-400">pedido enviado</span>
    @elseif ($exigeConfirmacao)
        <span class="text-[10px] leading-tight text-gray-500 dark:text-gray-400">pedir p/ remover</span>
    @endif
</button>
