@props(['registro' => null, 'tipo', 'itemId'])

@php
    $marcado = $registro && $registro->marcado_para_fazer && ! $registro->concluido;
    $dias = $marcado ? $registro->diasMarcadoParaFazer() : null;
    $pedido = $marcado && method_exists($registro, 'desmarcarFoiSolicitado') && $registro->desmarcarFoiSolicitado();
@endphp

@if ($marcado)
    <x-progresso.indicador-quero-fazer marcado />
    <span class="text-xs text-gray-500 dark:text-gray-400">
        quero fazer {{ $dias === 0 ? 'desde hoje' : 'há '.$dias.' '.($dias === 1 ? 'dia' : 'dias') }}
    </span>
    @if ($pedido)
        <x-filament::badge color="warning" size="sm">Pediu para desmarcar</x-filament::badge>
        <button type="button" wire:click.prevent="desmarcarQueroFazer('{{ $tipo }}', {{ $itemId }})" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Aceitar</button>
        <button type="button" wire:click.prevent="recusarPedidoDesmarcar('{{ $tipo }}', {{ $itemId }})" class="text-xs font-medium text-gray-600 hover:underline dark:text-gray-300">Manter</button>
    @else
        <button type="button" wire:click.prevent="desmarcarQueroFazer('{{ $tipo }}', {{ $itemId }})" class="text-xs text-gray-400 hover:text-gray-600 hover:underline dark:text-gray-500 dark:hover:text-gray-300">desmarcar</button>
    @endif
@endif
