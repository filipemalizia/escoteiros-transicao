@php
    /**
     * @var array{itens: array<int, string>, progresso: \Illuminate\Support\Collection, resumo: array{total: int, concluidos: int, percentual: int, completo: bool}} $acolhida
     */
    $jovemDaAcolhida = $this->getRecord();
    $resumoAcolhida = $acolhida['resumo'];
    $promessaRealizada = $jovemDaAcolhida->promessaRealizada();
    $promessaDeOutroRamo = $jovemDaAcolhida->data_promessa !== null && ! $promessaRealizada;
    $aguardandoAcolhida = $acolhida['progresso']->filter(fn ($registro) => $registro->solicitado_pelo_jovem && ! $registro->concluido)->count();
@endphp

@if ($resumoAcolhida['total'] > 0)
    <x-filament::section class="mt-6" :collapsible="true" :collapsed="$resumoAcolhida['completo'] && $promessaRealizada">
        <x-slot name="heading">
            <span class="flex flex-wrap items-center gap-2">
                Período de Acolhida
                @if ($resumoAcolhida['completo'])
                    <x-filament::badge color="success">100%</x-filament::badge>
                @else
                    <x-filament::badge color="gray">{{ $resumoAcolhida['concluidos'] }} de {{ $resumoAcolhida['total'] }}</x-filament::badge>
                @endif
                @if ($aguardandoAcolhida > 0)
                    <x-filament::badge color="warning">{{ $aguardandoAcolhida }} aguardando avaliação</x-filament::badge>
                @endif
                @if ($promessaRealizada)
                    <x-filament::badge color="success">Promessa em {{ $jovemDaAcolhida->data_promessa->format('d/m/Y') }}</x-filament::badge>
                @else
                    <x-filament::badge color="warning">Promessa pendente</x-filament::badge>
                @endif
            </span>
        </x-slot>

        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
            O que o jovem precisa cumprir antes da Promessa. Não bloqueia a marcação do progresso, mas os distintivos só são entregues depois da Promessa.
        </p>

        <ul class="space-y-2">
            @foreach ($acolhida['itens'] as $ordemAcolhida => $textoAcolhida)
                @php
                    $registroAcolhida = $acolhida['progresso'][$ordemAcolhida] ?? null;
                    $concluidoAcolhida = (bool) ($registroAcolhida?->concluido);
                    $solicitadoAcolhida = (bool) ($registroAcolhida?->solicitado_pelo_jovem);
                    $acaoCheckboxAcolhida = $solicitadoAcolhida ? "abrirAvaliacao('acolhida', {$ordemAcolhida})" : "toggleAcolhida({$ordemAcolhida})";
                @endphp
                <li wire:key="item-acolhida-{{ $ordemAcolhida }}-{{ $concluidoAcolhida ? 1 : 0 }}" class="flex flex-wrap items-start gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                    <label class="-mx-3 flex flex-1 cursor-pointer items-start gap-3 px-3">
                        <input
                            type="checkbox"
                            wire:click="{{ $acaoCheckboxAcolhida }}"
                            @checked($concluidoAcolhida)
                            class="mt-0.5 h-6 w-6 shrink-0 rounded border-gray-300 accent-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-1 dark:border-gray-600 dark:focus:ring-offset-gray-900"
                        />
                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                            <span>{{ $textoAcolhida }}</span>
                            @if ($solicitadoAcolhida && ! $concluidoAcolhida)
                                <x-filament::badge color="warning" size="sm">Aguardando avaliação</x-filament::badge>
                            @endif
                            @if ($registroAcolhida?->observacao_jovem)
                                <span class="block w-full text-xs italic text-gray-500 dark:text-gray-400">"{{ $registroAcolhida->observacao_jovem }}"</span>
                            @endif
                            @if ($concluidoAcolhida && $registroAcolhida?->data_conclusao)
                                <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                    Concluído em {{ $registroAcolhida->data_conclusao->format('d/m/Y') }}
                                    @if ($registroAcolhida->registradoPor)
                                        por {{ $registroAcolhida->registradoPor->name }}
                                    @endif
                                    <x-progresso.botao-editar-data wire-click="abrirEdicaoData('acolhida', {{ $ordemAcolhida }}, '{{ $registroAcolhida->data_conclusao->toDateString() }}')" />
                                </span>
                            @endif
                        </span>
                    </label>
                    @if ($solicitadoAcolhida && ! $concluidoAcolhida)
                        <button type="button" wire:click="abrirAvaliacao('acolhida', {{ $ordemAcolhida }})" class="shrink-0 rounded-lg bg-warning-600 px-2 py-1 text-xs font-medium text-white hover:bg-warning-500">
                            Avaliação
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Data da Promessa</label>
            @if ($promessaDeOutroRamo)
                <p class="mb-2 text-xs text-amber-600 dark:text-amber-400">
                    A Promessa registrada ({{ $jovemDaAcolhida->data_promessa->format('d/m/Y') }}) foi feita em outro ramo. Registre a Promessa deste ramo.
                </p>
            @endif
            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="date"
                    wire:model="dataPromessa"
                    max="{{ now()->toDateString() }}"
                    class="rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
                />
                <button type="button" wire:click="salvarDataPromessa" class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-500">
                    Salvar
                </button>
            </div>
            @error('dataPromessa')
                <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Deixe em branco e salve para remover a Promessa.</p>
        </div>
    </x-filament::section>
@endif
