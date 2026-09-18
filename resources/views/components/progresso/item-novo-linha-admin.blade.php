@props(['item', 'registro' => null, 'concluidoGeral' => false, 'solicitado' => false])

@php
    $marcadoDireto = (bool) ($registro?->concluido);
@endphp

<li wire:key="item-novo-{{ $item->id }}-{{ $concluidoGeral ? 1 : 0 }}" class="flex flex-wrap items-start gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
    <label class="-mx-3 flex flex-1 cursor-pointer items-start gap-3 px-3">
        <input
            type="checkbox"
            wire:click="toggleNovo({{ $item->id }})"
            @checked($concluidoGeral)
            class="mt-0.5 h-6 w-6 shrink-0 rounded border-gray-300 accent-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-1 dark:border-gray-600 dark:focus:ring-offset-gray-900"
        />
        <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
            <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item->codigo }}</span>
            <x-filament::badge
                :color="match ($item->tipo_acao) {
                    'Obrigatória' => 'danger',
                    'Variável' => 'warning',
                    'Substitutiva' => 'info',
                }"
                size="sm"
            >
                {{ $item->tipo_acao }}
            </x-filament::badge>
            <span>{{ $item->descricao }}</span>
            @if ($item->especialidade)
                <span class="text-gray-500 dark:text-gray-400">({{ $item->especialidade->tipo }}: {{ $item->especialidade->nome }})</span>
            @endif
            @if ($concluidoGeral && ! $marcadoDireto)
                <x-filament::badge color="info" size="sm" icon="heroicon-o-link">
                    via equivalência
                </x-filament::badge>
            @endif
            @if ($solicitado && ! $concluidoGeral)
                <x-filament::badge color="warning" size="sm">
                    Aguardando avaliação
                </x-filament::badge>
            @endif
            @if ($marcadoDireto && $registro?->data_conclusao)
                <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                    Concluído em {{ $registro->data_conclusao->format('d/m/Y') }}
                    @if ($registro->registradoPor)
                        por {{ $registro->registradoPor->name }}
                    @endif
                </span>
            @endif
            @if ($solicitado && $registro?->observacao_jovem)
                <span class="block w-full text-xs italic text-gray-500 dark:text-gray-400">
                    "{{ $registro->observacao_jovem }}"
                </span>
            @endif
        </span>
    </label>
    @if ($solicitado && ! $concluidoGeral)
        <button type="button" wire:click="abrirAvaliacao('novo', {{ $item->id }})" class="shrink-0 rounded-lg bg-warning-600 px-2 py-1 text-xs font-medium text-white hover:bg-warning-500">
            Avaliação
        </button>
    @endif
</li>
