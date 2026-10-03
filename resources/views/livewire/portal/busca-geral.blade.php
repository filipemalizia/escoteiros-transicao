@php
    $tipoParaSlug = fn (string $tipo) => $tipo === 'Insígnia' ? 'insignias' : 'especialidades';
    $itemParaEnviarAvaliacao = $this->getItemParaEnviarAvaliacao();
@endphp

<div>
    <input
        type="search"
        wire:model.live.debounce.300ms="busca"
        autofocus
        placeholder="Buscar especialidade, insígnia ou item de progressão..."
        class="w-full rounded-lg border-gray-300 text-sm placeholder:text-gray-400 focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
    />

    @if (blank($busca))
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Digite pra buscar em tudo: especialidades, insígnias e itens do Programa Novo.
        </p>
    @else
        @if ($especialidades->isEmpty() && $itensDeProgressao->isEmpty())
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Nada encontrado.</p>
        @endif

        @if ($especialidades->isNotEmpty())
            <div class="mt-6">
                <h2 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Especialidades e Insígnias</h2>
                <ul class="space-y-2">
                    @foreach ($especialidades as $especialidade)
                        <li>
                            <a
                                href="{{ route('portal.catalogo', $tipoParaSlug($especialidade->tipo), ['q' => $busca, 'abrir' => $especialidade->id]) }}"
                                class="flex flex-wrap items-start gap-2 rounded-xl border border-gray-200 p-3 text-sm text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5"
                            >
                                <x-progresso.badge :color="$especialidade->tipo === 'Insígnia' ? 'info' : 'gray'">
                                    {{ $especialidade->tipo }}
                                </x-progresso.badge>
                                <span class="min-w-0 flex-1"><x-progresso.destaque :texto="$especialidade->nome" :busca="$busca" /></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($itensDeProgressao->isNotEmpty())
            <div class="mt-6">
                <h2 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Itens de Progressão</h2>
                <ul class="space-y-2">
                    @foreach ($itensDeProgressao as $itemNovo)
                        <li class="rounded-xl border border-gray-200 p-3 text-sm dark:border-white/10">
                            <div class="flex flex-wrap items-start gap-2">
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $itemNovo->codigo }}</span>
                                <x-progresso.badge :color="match ($itemNovo->tipo_acao) {
                                    'Obrigatória' => 'danger',
                                    'Variável' => 'warning',
                                    'Substitutiva' => 'info',
                                    default => 'gray',
                                }">
                                    {{ $itemNovo->tipo_acao }}
                                </x-progresso.badge>
                                <span class="min-w-0 flex-1 text-gray-700 dark:text-gray-200"><x-progresso.destaque :texto="$itemNovo->descricao" :busca="$busca" /></span>
                                <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                    {{ $itemNovo->bloco->eixo->nome }} — {{ $itemNovo->bloco->titulo }}
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    wire:click="abrirEnvioAvaliacao({{ $itemNovo->id }})"
                                    class="rounded-lg bg-primary-600 px-2 py-1 text-xs font-medium text-white hover:bg-primary-500"
                                >
                                    Enviar para avaliação
                                </button>
                                <a
                                    href="{{ route('portal.eixos.show', $itemNovo->bloco->eixo, ['bloco' => $itemNovo->bloco_id, 'q' => $busca]) }}"
                                    class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                                >
                                    Abrir bloco
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    <x-progresso.modal
        :show="(bool) $itemParaEnviarAvaliacao"
        heading="Enviar para avaliação"
        wire-close-action="fecharEnvioAvaliacao"
    >
        @if ($itemParaEnviarAvaliacao)
            <p class="mb-3 text-sm text-gray-700 dark:text-gray-200">{{ $itemParaEnviarAvaliacao->descricao }}</p>

            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                Observação (opcional)
            </label>
            <textarea
                wire:model="observacoesAvaliacao.{{ $enviandoAvaliacaoItemId }}"
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
                    wire:click="solicitarNovo({{ $enviandoAvaliacaoItemId }})"
                    wire:loading.attr="disabled"
                    wire:target="solicitarNovo({{ $enviandoAvaliacaoItemId }})"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-wait disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="solicitarNovo({{ $enviandoAvaliacaoItemId }})">Confirmar envio</span>
                    <span wire:loading wire:target="solicitarNovo({{ $enviandoAvaliacaoItemId }})">Enviando...</span>
                </button>
            </div>
        @endif
    </x-progresso.modal>
</div>
