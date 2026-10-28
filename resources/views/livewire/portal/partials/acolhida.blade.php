@php
    /**
     * @var array{itens: array<int, string>, progresso: \Illuminate\Support\Collection, resumo: array{total: int, concluidos: int, percentual: int, completo: bool}} $acolhida
     * @var bool $promessaRealizada
     */
    $resumoAcolhida = $acolhida['resumo'];
    // Aberta enquanto falta algo; recolhida quando está tudo feito e a Promessa já foi registrada.
    $acolhidaAberta = ! ($resumoAcolhida['completo'] && $promessaRealizada);
@endphp

<details class="group mt-6 rounded-xl border border-gray-200 dark:border-white/10" @if ($acolhidaAberta) open @endif>
    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 p-4">
        <span class="text-base font-semibold text-gray-950 dark:text-white">Período de Acolhida</span>
        @if ($resumoAcolhida['completo'])
            <x-progresso.badge color="success">100%</x-progresso.badge>
        @else
            <x-progresso.badge color="gray">{{ $resumoAcolhida['concluidos'] }} de {{ $resumoAcolhida['total'] }}</x-progresso.badge>
        @endif
        @if ($promessaRealizada)
            <x-progresso.badge color="success">Promessa em {{ $jovem->data_promessa->format('d/m/Y') }}</x-progresso.badge>
        @endif
        <x-filament::icon icon="heroicon-m-chevron-down" class="ml-auto h-5 w-5 text-gray-400 transition-transform group-open:rotate-180" />
    </summary>

    <div class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-white/10">
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
            Para fazer a sua Promessa, cumpra estes itens. Quando terminar um, envie para o seu chefe avaliar.
        </p>

        <ul class="space-y-1">
            @foreach ($acolhida['itens'] as $ordemAcolhida => $textoAcolhida)
                @php
                    $registroAcolhida = $acolhida['progresso'][$ordemAcolhida] ?? null;
                    $concluidoAcolhida = (bool) ($registroAcolhida?->concluido);
                    $solicitadoAcolhida = (bool) ($registroAcolhida?->solicitado_pelo_jovem);
                    $podeEnviarAcolhida = ! $concluidoAcolhida && ! $solicitadoAcolhida;
                @endphp
                <li
                    @if ($podeEnviarAcolhida) wire:click="abrirEnvioAcolhida({{ $ordemAcolhida }})" @endif
                    class="flex items-start gap-3 rounded-lg px-1 py-2 {{ $podeEnviarAcolhida ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5' : '' }}"
                >
                    <x-progresso.status-icone :concluido="$concluidoAcolhida" :solicitado="$solicitadoAcolhida" class="mt-0.5" />
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-200">
                        {{ $textoAcolhida }}
                        @if ($concluidoAcolhida && $registroAcolhida?->data_conclusao)
                            <span class="block text-xs text-gray-400 dark:text-gray-500">Concluído em {{ $registroAcolhida->data_conclusao->format('d/m/Y') }}</span>
                        @elseif ($solicitadoAcolhida && $registroAcolhida?->solicitado_em)
                            <span class="block text-xs text-gray-400 dark:text-gray-500">Enviado em {{ $registroAcolhida->solicitado_em->format('d/m/Y') }}, aguardando o chefe</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</details>
