@php
    $itemParaEnviarAvaliacao = $this->getItemParaEnviarAvaliacao();
    $textoItemParaEnviarAvaliacao = $enviandoAvaliacaoTipo === 'especialidade'
        ? $itemParaEnviarAvaliacao?->texto
        : $itemParaEnviarAvaliacao?->descricao;
    $editandoPrazo = (bool) $editandoPrazoTipo;
@endphp

<div>
    <h1 class="text-xl font-bold text-gray-950 dark:text-white">Quero Fazer</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Itens que você marcou como próximos passos.</p>

    @if (empty($itens))
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Nada marcado como "quero fazer" no momento.</p>
    @else
        <ul class="mt-6 space-y-3">
            @foreach ($itens as $item)
                @php
                    $wireClickQueroFazer = "toggleQueroFazer('{$item['tipo']}', {$item['item_id']})";
                @endphp
                <li class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                    <div class="flex items-start gap-3">
                        <x-progresso.indicador-quero-fazer marcado class="mt-0.5" />
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['texto'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['contexto'] }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                @if ($item['data_alvo'])
                                    <span>Prazo: {{ $item['data_alvo']->format('d/m/Y') }}</span>
                                @else
                                    <span>Sem prazo definido</span>
                                @endif
                                <button
                                    type="button"
                                    wire:click="abrirEdicaoPrazo('{{ $item['tipo'] }}', {{ $item['item_id'] }}, {{ $item['data_alvo'] ? "'".$item['data_alvo']->toDateString()."'" : 'null' }})"
                                    class="text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    {{ $item['data_alvo'] ? 'editar' : 'definir prazo' }}
                                </button>
                            </div>
                            @if ($item['solicitado'])
                                <x-progresso.badge color="warning" class="mt-1">Aguardando avaliação</x-progresso.badge>
                            @else
                                <button
                                    type="button"
                                    wire:click="abrirEnvioAvaliacao('{{ $item['tipo'] }}', {{ $item['item_id'] }})"
                                    class="mt-1.5 rounded-lg bg-primary-600 px-2 py-1 text-xs font-medium text-white hover:bg-primary-500"
                                >
                                    Enviar para avaliação
                                </button>
                            @endif
                        </div>
                        <x-progresso.botao-quero-fazer
                            marcado
                            :wire-click="$wireClickQueroFazer"
                            confirm="Remover este item da sua lista de 'quero fazer'?"
                        />
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

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

    <x-progresso.modal
        :show="$editandoPrazo"
        heading="Definir prazo"
        wire-close-action="fecharEdicaoPrazo"
    >
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
            Pra quando você quer fazer isso? Deixe em branco se não quiser definir um prazo.
        </p>
        <input
            type="date"
            wire:model="editandoPrazoValor"
            min="{{ now()->toDateString() }}"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
        />

        <div class="mt-4 flex justify-end gap-2">
            <button
                type="button"
                wire:click="fecharEdicaoPrazo"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
            >
                Cancelar
            </button>
            <button
                type="button"
                wire:click="salvarPrazo"
                class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-500"
            >
                Salvar
            </button>
        </div>
    </x-progresso.modal>
</div>
