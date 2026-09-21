@php
    $progressoNovoMap = $this->getProgressoNovoMap();
    $progressoPersonalizadoMap = $this->getProgressoPersonalizadoMap();
    $itemParaEnviarAvaliacao = $this->getItemParaEnviarAvaliacao();
    $textoItemParaEnviarAvaliacao = $itemParaEnviarAvaliacao?->descricao;
    $detalhesPendenciaNovo = collect($this->getPendenciasNovo())->keyBy(fn (array $p) => $p['bloco']->id);

    $corStatus = fn (string $status) => match ($status) {
        'Concluído' => 'success',
        'Parcial' => 'warning',
        default => 'gray',
    };
@endphp

<div>
    <div class="divide-y divide-gray-100 dark:divide-white/10">
        @foreach ($eixo->blocos as $bloco)
            @php $statusBloco = $this->statusBloco($bloco); @endphp
            <x-progresso.accordion
                :id="'bloco-'.$bloco->id"
                :heading="$bloco->titulo"
                :description="$bloco->descricao"
                :status="$statusBloco['status']"
                :status-color="$corStatus($statusBloco['status'])"
                :pendencia="$detalhesPendenciaNovo[$bloco->id]['detalhe'] ?? null"
                com-imagem
                :imagem-url="$bloco->categoriaImagem?->getFirstMediaUrl('imagem')"
                :imagem-colorida="$statusBloco['status'] === 'Concluído'"
            >
                @php
                    $itensVisiveis = $this->itensVisiveisDoBloco($bloco);
                    $itensPrincipais = $itensVisiveis->whereIn('tipo_acao', ['Obrigatória', 'Variável']);
                    $itensSubstitutivas = $itensVisiveis->where('tipo_acao', 'Substitutiva');
                @endphp

                <ul class="space-y-1">
                    @foreach ($itensPrincipais as $item)
                        <x-progresso.item-novo-linha
                            :item="$item"
                            :registro="$progressoNovoMap[$item->id] ?? null"
                            :concluido-geral="$this->itemNovoConcluido($item)"
                            :solicitado="(bool) (($progressoNovoMap[$item->id] ?? null)?->solicitado_pelo_jovem)"
                        />
                    @endforeach
                </ul>

                @if ($bloco->equivalenciasBloco->isNotEmpty())
                    <div class="mt-3 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <div class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                            Itens do Programa Antigo que também contam como Ação Variável deste bloco
                            ({{ $statusBloco['variaveis_concluidas_via_bloco'] }} concluído(s))
                        </div>
                        <ul class="space-y-2">
                            @foreach ($bloco->equivalenciasBloco as $equivalenciaBloco)
                                @php
                                    $itemAntigoVinculado = $equivalenciaBloco->itemAntigo;
                                    $concluidoViaBloco = $itemAntigoVinculado && $this->itemAntigoConcluido($itemAntigoVinculado);
                                @endphp
                                @if ($itemAntigoVinculado)
                                    <li class="flex items-start gap-3 rounded-lg px-1 py-1">
                                        <x-progresso.badge :color="$concluidoViaBloco ? 'success' : 'gray'">
                                            {{ $concluidoViaBloco ? 'Concluído' : 'Pendente' }}
                                        </x-progresso.badge>
                                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                            <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $itemAntigoVinculado->codigo }}</span>
                                            <span>{{ $itemAntigoVinculado->descricao }}</span>
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php $itensPersonalizados = $this->getItensPersonalizadosDoBloco($bloco); @endphp
                @if ($itensPersonalizados->isNotEmpty())
                    <div class="mt-3 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <div class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                            Itens personalizados pra você
                        </div>
                        <ul class="space-y-1">
                            @foreach ($itensPersonalizados as $itemPersonalizado)
                                @php
                                    $registroPersonalizado = $progressoPersonalizadoMap[$itemPersonalizado->id] ?? null;
                                    $concluidoPersonalizado = (bool) ($registroPersonalizado?->concluido);
                                    $solicitadoPersonalizado = (bool) ($registroPersonalizado?->solicitado_pelo_jovem);
                                    $podeEnviarPersonalizado = ! $concluidoPersonalizado && ! $solicitadoPersonalizado;
                                @endphp
                                <li
                                    @if ($podeEnviarPersonalizado) wire:click="abrirEnvioAvaliacao('personalizado', {{ $itemPersonalizado->id }})" @endif
                                    class="flex flex-wrap items-start gap-3 rounded-lg px-1 py-2 {{ $podeEnviarPersonalizado ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5' : '' }}"
                                >
                                    <x-progresso.status-icone :concluido="$concluidoPersonalizado" :solicitado="$solicitadoPersonalizado" class="mt-0.5" />
                                    <span class="flex flex-1 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                        <x-progresso.badge color="warning">Personalizado</x-progresso.badge>
                                        <span>{{ $itemPersonalizado->descricao }}</span>
                                        @if ($concluidoPersonalizado && $registroPersonalizado?->data_conclusao)
                                            <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                                Concluído em {{ $registroPersonalizado->data_conclusao->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        @if ($solicitadoPersonalizado && $registroPersonalizado?->solicitado_em)
                                            <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                                Enviado em {{ $registroPersonalizado->solicitado_em->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($itensSubstitutivas->isNotEmpty())
                    <ul class="mt-3 space-y-1">
                        @foreach ($itensSubstitutivas as $item)
                            <x-progresso.item-novo-linha
                                :item="$item"
                                :registro="$progressoNovoMap[$item->id] ?? null"
                                :concluido-geral="$this->itemNovoConcluido($item)"
                                :solicitado="(bool) (($progressoNovoMap[$item->id] ?? null)?->solicitado_pelo_jovem)"
                            />
                        @endforeach
                    </ul>
                @endif
            </x-progresso.accordion>
        @endforeach
    </div>

    <x-progresso.modal
        :show="(bool) $itemParaEnviarAvaliacao"
        heading="Enviar para avaliação"
        wire-close-action="fecharEnvioAvaliacao"
    >
        @if ($itemParaEnviarAvaliacao)
            <p class="mb-3 text-sm text-gray-700 dark:text-gray-200">{{ $textoItemParaEnviarAvaliacao }}</p>

            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                Observação (opcional)
            </label>
            <textarea
                wire:model="observacoesAvaliacao.{{ $enviandoAvaliacaoTipo }}.{{ $enviandoAvaliacaoItemId }}"
                rows="3"
                placeholder="Algo que ajude o chefe a avaliar..."
                class="w-full rounded-lg border-gray-300 text-sm placeholder:text-gray-400 focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
            ></textarea>

            <div class="mt-4 flex justify-end gap-2">
                <button
                    type="button"
                    wire:click="fecharEnvioAvaliacao"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    wire:click="enviarAvaliacaoAtual"
                    wire:loading.attr="disabled"
                    wire:target="enviarAvaliacaoAtual"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-wait disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="enviarAvaliacaoAtual">Confirmar envio</span>
                    <span wire:loading wire:target="enviarAvaliacaoAtual">Enviando...</span>
                </button>
            </div>
        @endif
    </x-progresso.modal>
</div>
