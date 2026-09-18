@props(['item', 'registro' => null, 'concluidoGeral' => false, 'solicitado' => false])

@php
    $marcadoDireto = (bool) ($registro?->concluido);
    $podeEnviar = ! $concluidoGeral && ! $solicitado;

    $corTipoAcao = fn (string $tipo) => match ($tipo) {
        'Obrigatória' => 'danger',
        'Variável' => 'warning',
        'Substitutiva' => 'info',
        default => 'gray',
    };
@endphp

<li
    @if ($podeEnviar) wire:click="abrirEnvioAvaliacao('novo', {{ $item->id }})" @endif
    class="flex flex-wrap items-start gap-3 rounded-lg px-1 py-2 {{ $podeEnviar ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5' : '' }}"
>
    <x-progresso.status-icone :concluido="$concluidoGeral" :solicitado="$solicitado" class="mt-0.5" />
    <span class="flex flex-1 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
        <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item->codigo }}</span>
        <x-progresso.badge :color="$corTipoAcao($item->tipo_acao)">{{ $item->tipo_acao }}</x-progresso.badge>
        <span>{{ $item->descricao }}</span>
        @if ($item->especialidade)
            <span class="text-gray-500 dark:text-gray-400">({{ $item->especialidade->tipo }}: {{ $item->especialidade->nome }})</span>
        @endif
        @if ($concluidoGeral && ! $marcadoDireto)
            <x-progresso.badge color="info">via equivalência</x-progresso.badge>
        @endif
        @if ($marcadoDireto && $registro?->data_conclusao)
            <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                Concluído em {{ $registro->data_conclusao->format('d/m/Y') }}
            </span>
        @endif
        @if ($solicitado && $registro?->solicitado_em)
            <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                Enviado em {{ $registro->solicitado_em->format('d/m/Y') }}
            </span>
        @endif
    </span>
</li>
