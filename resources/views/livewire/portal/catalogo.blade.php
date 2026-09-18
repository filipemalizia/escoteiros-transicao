@php
    $especialidades = $this->especialidadesFiltradas();
    $eixos = $this->getEixosNovos();
    $especialidadeAberta = $this->getEspecialidadeAberta();
    $itemParaEnviarAvaliacao = $this->getItemParaEnviarAvaliacao();
    $progressoEspecialidadeMap = $this->getProgressoEspecialidadeMap();

    if ($especialidadeAberta) {
        $statusAberta = $this->statusEspecialidade($especialidadeAberta);
        $nivelParaImagemAberta = $statusAberta['nivel_atingido'] ?? ($statusAberta['status'] === 'Concluído' ? 1 : 0);
        $coloridaAberta = $nivelParaImagemAberta >= 1;
        $rotuloAberta = $statusAberta['nivel_atingido'] !== null
            ? "Nível {$statusAberta['nivel_atingido']}"
            : $statusAberta['status'];
        $dataAberta = $statusAberta['nivel_atingido']
            ? $this->dataNivelEspecialidade($especialidadeAberta, $statusAberta['nivel_atingido'])
            : ($statusAberta['status'] === 'Concluído' ? $this->dataConclusaoEspecialidade($especialidadeAberta) : null);
    }
@endphp

<div>
    <div class="mb-4 flex gap-2">
        <button
            type="button"
            wire:click="$set('aba', 'minhas')"
            class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $aba === 'minhas' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200' }}"
        >
            Minhas Especialidades
        </button>
        <button
            type="button"
            wire:click="$set('aba', 'todas')"
            class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $aba === 'todas' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200' }}"
        >
            Todas
        </button>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input
            type="search"
            wire:model.live.debounce.300ms="busca"
            placeholder="Buscar por nome..."
            class="flex-1 rounded-lg border-gray-300 text-sm placeholder:text-gray-400 focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
        />
        <select
            wire:model.live="eixoId"
            class="rounded-lg border-gray-300 text-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
        >
            <option value="">Todos os eixos</option>
            @foreach ($eixos as $eixo)
                <option value="{{ $eixo->id }}">{{ $eixo->nome }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($especialidades as $especialidade)
            @php
                $status = $this->statusEspecialidade($especialidade);
                $rotulo = $status['nivel_atingido'] !== null
                    ? "Nível {$status['nivel_atingido']}"
                    : $status['status'];
                $colorida = ($status['nivel_atingido'] ?? 0) >= 1 || $status['status'] === 'Concluído';
                $nivelParaImagem = $status['nivel_atingido'] ?? ($status['status'] === 'Concluído' ? 1 : 0);
                $data = $status['nivel_atingido']
                    ? $this->dataNivelEspecialidade($especialidade, $status['nivel_atingido'])
                    : ($status['status'] === 'Concluído' ? $this->dataConclusaoEspecialidade($especialidade) : null);
            @endphp
            <button
                type="button"
                wire:click="abrirEspecialidade({{ $especialidade->id }})"
                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 text-left hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
            >
                <x-progresso.imagem-badge
                    :url="$especialidade->urlImagemParaNivel($nivelParaImagem)"
                    :colorida="$colorida"
                    :alt="$especialidade->nome"
                />
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $especialidade->nome }}</div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <span class="h-1.5 w-1.5 rounded-full {{ $colorida ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                        {{ $rotulo }}
                        @if ($data)
                            ({{ $data->format('d/m/Y') }})
                        @endif
                    </div>
                </div>
            </button>
        @empty
            <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">Nada encontrado.</p>
        @endforelse
    </div>

    <x-progresso.modal
        :show="(bool) $especialidadeAberta"
        wire-close-action="fecharEspecialidade"
        size="sm:max-w-lg"
    >
        @if ($especialidadeAberta)
            <div class="mb-4 flex flex-col items-center gap-2 text-center">
                <x-progresso.imagem-badge
                    :url="$especialidadeAberta->urlImagemParaNivel($nivelParaImagemAberta)"
                    :colorida="$coloridaAberta"
                    :alt="$especialidadeAberta->nome"
                    size="h-24 w-24"
                />
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $especialidadeAberta->nome }}</h3>
                <x-progresso.badge :color="$coloridaAberta ? 'success' : 'gray'">
                    {{ $rotuloAberta }}{{ $dataAberta ? ' ('.$dataAberta->format('d/m/Y').')' : '' }}
                </x-progresso.badge>
            </div>

            @if ($especialidadeAberta->descricao || $especialidadeAberta->regra_niveis)
                <div class="mb-4 space-y-1 text-left">
                    @if ($especialidadeAberta->descricao)
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $especialidadeAberta->descricao }}</p>
                    @endif
                    @if ($especialidadeAberta->regra_niveis)
                        <p class="inline-block rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $especialidadeAberta->regra_niveis }}
                        </p>
                    @endif
                </div>
            @endif

            @foreach ($especialidadeAberta->grupos as $grupo)
                <div class="mb-3">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        {{ $grupo->chave }}
                        @if ($grupo->quantidade_minima)
                            (mínimo {{ $grupo->quantidade_minima }} de {{ $grupo->itens->count() }})
                        @endif
                    </div>
                    <ul class="space-y-1">
                        @foreach ($grupo->itens as $item)
                            @php
                                $registroEspecialidade = $progressoEspecialidadeMap[$item->id] ?? null;
                                $concluidoEspecialidade = (bool) ($registroEspecialidade?->concluido);
                                $solicitadoEspecialidade = (bool) ($registroEspecialidade?->solicitado_pelo_jovem);
                                $podeEnviarEspecialidade = ! $concluidoEspecialidade && ! $solicitadoEspecialidade;
                            @endphp
                            <li
                                @if ($podeEnviarEspecialidade) wire:click="abrirEnvioAvaliacao({{ $item->id }})" @endif
                                class="flex flex-wrap items-start gap-3 rounded-lg px-1 py-2 {{ $podeEnviarEspecialidade ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5' : '' }}"
                            >
                                <x-progresso.status-icone :concluido="$concluidoEspecialidade" :solicitado="$solicitadoEspecialidade" class="mt-0.5" />
                                <span class="flex flex-1 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                    <span>{{ $item->ordem ? "{$item->ordem}. " : '' }}{{ $item->texto }}</span>
                                    @if ($concluidoEspecialidade && $registroEspecialidade?->data_conclusao)
                                        <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                            Concluído em {{ $registroEspecialidade->data_conclusao->format('d/m/Y') }}
                                        </span>
                                    @endif
                                    @if ($solicitadoEspecialidade && $registroEspecialidade?->solicitado_em)
                                        <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                            Enviado em {{ $registroEspecialidade->solicitado_em->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        @endif
    </x-progresso.modal>

    <x-progresso.modal
        :show="(bool) $itemParaEnviarAvaliacao"
        heading="Enviar para avaliação"
        wire-close-action="fecharEnvioAvaliacao"
        z-index="z-[60]"
    >
        @if ($itemParaEnviarAvaliacao)
            <p class="mb-3 text-sm text-gray-700 dark:text-gray-200">{{ $itemParaEnviarAvaliacao->texto }}</p>

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
                    wire:click="solicitarEspecialidade({{ $enviandoAvaliacaoItemId }})"
                    wire:loading.attr="disabled"
                    wire:target="solicitarEspecialidade({{ $enviandoAvaliacaoItemId }})"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-wait disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="solicitarEspecialidade({{ $enviandoAvaliacaoItemId }})">Confirmar envio</span>
                    <span wire:loading wire:target="solicitarEspecialidade({{ $enviandoAvaliacaoItemId }})">Enviando...</span>
                </button>
            </div>
        @endif
    </x-progresso.modal>
</div>
