@php
    $jovem = $this->jovem();
    $percentualNovo = $this->getPercentualNovo();
    $percentualGamificadoNovo = $this->getPercentualGamificadoNovo();
    $resumoNovo = $this->getResumoNovo();
    $elegivelNovo = $this->getElegivelReconhecimentoNovo();
    $nomeReconhecimentoNovo = $this->getNomeReconhecimentoNovo();
    $requisitosComplementaresNovo = $this->getRequisitosComplementaresNovo();
    $eixos = $this->getEixosNovos();
    $trilhaEtapaNovo = $this->getTrilhaEtapaNovo();
@endphp

<div>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-950 dark:text-white">Olá, {{ $jovem->primeiroNome() }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $jovem->ramoAtual->nome }}</p>
        </div>
        <button
            type="button"
            wire:click="sair"
            class="shrink-0 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
        >
            Sair
        </button>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <div class="text-sm text-gray-500 dark:text-gray-400">Progresso</div>
        <div class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $percentualGamificadoNovo['percentual'] }}%</div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $percentualNovo['concluidos'] }} de {{ $percentualNovo['total'] }} blocos concluídos
        </div>
        @if (! empty($trilhaEtapaNovo))
            <button type="button" wire:click="abrirModalEtapa" class="mt-4 block w-full overflow-x-auto">
                <div class="min-w-max px-1 pb-1">
                    <div class="flex items-center gap-1">
                        @foreach ($trilhaEtapaNovo as $marco)
                            @if (! $loop->first)
                                <div class="h-1.5 w-8 shrink-0 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10 sm:w-12">
                                    <div class="h-full rounded-full bg-primary-600" style="width: {{ $marco['progresso'] * 100 }}%"></div>
                                </div>
                            @endif
                            <x-progresso.imagem-badge
                                :url="$marco['imagem_url']"
                                :percentual="$marco['progresso']"
                                :alt="$marco['label']"
                                size="h-16 w-16"
                                :id="$marco['atual'] ? 'trilha-etapa-atual' : null"
                                class="shrink-0 {{ $marco['atual'] ? 'rounded-2xl ring-4 ring-primary-200 dark:ring-primary-900' : '' }}"
                            />
                        @endforeach
                    </div>
                    <div class="mt-1 flex gap-1">
                        @foreach ($trilhaEtapaNovo as $marco)
                            @if (! $loop->first)
                                <div class="w-8 shrink-0 sm:w-12"></div>
                            @endif
                            <div class="w-16 shrink-0 text-center">
                                <div class="truncate text-[11px] font-medium {{ $marco['atual'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $marco['label'] }}
                                </div>
                                @if ($marco['data_alcancado'])
                                    <div class="truncate text-[10px] text-gray-400 dark:text-gray-500">
                                        {{ $marco['data_alcancado']->format('d/m/Y') }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </button>
        @endif
    </div>

    <x-progresso.modal :show="$modalConquistasAberto" heading="Novidades!" wire-close-action="fecharModalConquistas">
        <ul class="space-y-3">
            @foreach ($conquistasNovas as $conquista)
                <li class="flex items-center gap-3">
                    <x-progresso.imagem-badge :url="$conquista['imagem_url']" :colorida="true" :alt="$conquista['titulo']" size="h-12 w-12" />
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $conquista['titulo'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $conquista['subtitulo'] }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $conquista['data'] }}</div>
                    </div>
                    @if (in_array($conquista['tipo'], ['especialidade', 'insignia', 'etapa', 'reconhecimento'], true))
                        <x-progresso.botao-compartilhar
                            :tipo="$conquista['tipo']"
                            :titulo="$conquista['titulo']"
                            :imagem-url="$conquista['imagem_data_uri']"
                            :nivel="$conquista['nivel'] ?? null"
                            :jovem="$jovem"
                        />
                    @endif
                </li>
            @endforeach
        </ul>
    </x-progresso.modal>

    <x-progresso.modal :show="$modalEtapaAberto" heading="Etapas" wire-close-action="fecharModalEtapa">
        <ul class="space-y-3">
            @foreach ($trilhaEtapaNovo as $marco)
                <li class="flex items-center gap-3">
                    <x-progresso.imagem-badge :url="$marco['imagem_url']" :colorida="$marco['alcancado']" :alt="$marco['label']" size="h-10 w-10" />
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium {{ $marco['atual'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-200' }}">
                            {{ $marco['label'] }}
                        </div>
                        @if (! $marco['alcancado'])
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                @if ($marco['faltam'] > 0)
                                    Faltam {{ $marco['faltam'] }} {{ $marco['faltam'] === 1 ? 'bloco' : 'blocos' }}
                                @else
                                    Faltam os requisitos complementares
                                @endif
                            </div>
                        @elseif ($marco['tipo'] === 'reconhecimento')
                            <div class="text-xs text-green-600 dark:text-green-400">Conquistado!</div>
                        @endif
                    </div>
                    @if ($marco['alcancado'])
                        <x-progresso.botao-compartilhar
                            :tipo="$marco['tipo']"
                            :titulo="$marco['label']"
                            :imagem-url="$marco['imagem_data_uri']"
                            :jovem="$jovem"
                        />
                    @endif
                </li>
            @endforeach
        </ul>
    </x-progresso.modal>

    <div class="mt-6 space-y-2">
        @foreach ($eixos as $eixo)
            @php
                $blocosDoEixo = $eixo->blocos;
                $blocosConcluidosDoEixo = $blocosDoEixo->filter(fn ($bloco) => $this->statusBloco($bloco)['status'] === 'Concluído')->count();
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-white/10">
                <a
                    href="{{ route('portal.eixos.show', $eixo) }}"
                    class="flex items-center gap-3 rounded-t-xl p-3 hover:bg-gray-50 dark:hover:bg-white/5"
                >
                    <x-progresso.imagem-badge
                        :url="$eixo->categoriaImagem?->getFirstMediaUrl('imagem')"
                        :percentual="$this->percentualGamificadoEixo($eixo)"
                        :alt="$eixo->nome"
                        size="h-14 w-14"
                        class="overflow-hidden rounded-2xl"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $eixo->nome }}</div>
                        @if ($blocosDoEixo->isNotEmpty())
                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                @foreach ($blocosDoEixo as $bloco)
                                    <span
                                        class="h-2.5 w-2.5 shrink-0 rounded-full {{ $this->statusBloco($bloco)['status'] !== 'Concluído' ? 'bg-gray-300 dark:bg-gray-600' : '' }}"
                                        @if ($this->statusBloco($bloco)['status'] === 'Concluído') style="background-color: {{ $eixo->cor() }}" @endif
                                    ></span>
                                @endforeach
                                <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">
                                    ({{ $blocosConcluidosDoEixo }} de {{ $blocosDoEixo->count() }})
                                </span>
                            </div>
                        @endif
                    </div>
                    @if ($this->eixoConcluido($eixo))
                        <x-progresso.botao-compartilhar
                            tipo="eixo"
                            :titulo="$eixo->nome"
                            :imagem-url="$eixo->categoriaImagem?->dataUriImagem()"
                            :jovem="$jovem"
                        />
                    @endif
                </a>

                @if ($blocosDoEixo->isNotEmpty())
                    <div class="space-y-2 border-t border-gray-100 p-3 dark:border-white/10">
                        @foreach ($blocosDoEixo as $bloco)
                            @php $percentualBloco = $this->percentualGamificadoBloco($bloco); @endphp
                            <div class="flex items-center gap-2">
                                <x-progresso.imagem-badge
                                    :url="$bloco->categoriaImagem?->getFirstMediaUrl('imagem')"
                                    :colorida="$this->statusBloco($bloco)['status'] === 'Concluído'"
                                    :alt="$bloco->titulo"
                                    size="h-8 w-8"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs font-medium text-gray-700 dark:text-gray-200">{{ $bloco->titulo }}</div>
                                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                        <div
                                            class="h-full rounded-full"
                                            style="width: {{ max(0, min(100, $percentualBloco * 100)) }}%; background-color: {{ $eixo->cor() }}"
                                        ></div>
                                    </div>
                                </div>
                                @if ($this->statusBloco($bloco)['status'] === 'Concluído')
                                    <x-progresso.botao-compartilhar
                                        tipo="bloco"
                                        :titulo="$bloco->titulo"
                                        :imagem-url="$bloco->categoriaImagem?->dataUriImagem()"
                                        :jovem="$jovem"
                                    />
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3">
        <a
            href="{{ route('portal.catalogo', 'especialidades') }}"
            class="flex flex-col items-center gap-2 rounded-xl border border-gray-200 p-4 text-center hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
        >
            <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 dark:bg-white/5">
                <x-filament::icon icon="heroicon-o-trophy" class="h-8 w-8 text-gray-400 dark:text-gray-500" />
            </div>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Especialidades</span>
        </a>

        <a
            href="{{ route('portal.catalogo', 'insignias') }}"
            class="flex flex-col items-center gap-2 rounded-xl border border-gray-200 p-4 text-center hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
        >
            <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 dark:bg-white/5">
                <x-filament::icon icon="heroicon-o-shield-check" class="h-8 w-8 text-gray-400 dark:text-gray-500" />
            </div>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Insígnias</span>
        </a>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <div class="flex items-center gap-3">
            @if ($imagemReconhecimento = $this->getImagemReconhecimento())
                <x-progresso.imagem-badge :url="$imagemReconhecimento" :colorida="$elegivelNovo" :alt="$nomeReconhecimentoNovo" size="h-14 w-14" />
            @endif
            <h2 class="min-w-0 flex-1 font-semibold text-gray-950 dark:text-white">
                {{ $elegivelNovo ? '🎉 Elegível ao '.$nomeReconhecimentoNovo.'!' : 'Reconhecimento: '.$nomeReconhecimentoNovo }}
            </h2>
            @if ($elegivelNovo)
                <x-progresso.botao-compartilhar
                    tipo="reconhecimento"
                    :titulo="$nomeReconhecimentoNovo"
                    :imagem-url="$this->getDataUriImagemReconhecimento()"
                    :jovem="$jovem"
                />
            @endif
        </div>

        <ul class="mt-2 space-y-1 text-sm font-medium text-gray-700 dark:text-gray-200">
            <li>Blocos concluídos: {{ $resumoNovo['blocos_concluidos'] }} de {{ $resumoNovo['blocos_total'] }}</li>
            <li>Ações Obrigatórias concluídas: {{ $resumoNovo['obrigatorias_concluidas'] }} de {{ $resumoNovo['obrigatorias_total'] }}</li>
            <li>Ações Variáveis (dentro do mínimo exigido): {{ $resumoNovo['variaveis_atingidas'] }} de {{ $resumoNovo['variaveis_minimas_total'] }}</li>
        </ul>

        @if ($elegivelNovo)
            <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                Todos os 18 blocos e requisitos complementares foram atendidos.
            </p>
        @else
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ainda não elegível.</p>
        @endif

        @if (! empty($requisitosComplementaresNovo))
            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10">
                <div class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Requisitos Complementares</div>
                <ul class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($requisitosComplementaresNovo as $requisito)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2 first:pt-0 last:pb-0">
                            <span class="text-sm text-gray-700 dark:text-gray-200">
                                {{ $requisito['label'] }}
                                @if (isset($requisito['meta']))
                                    <span class="text-gray-400 dark:text-gray-500">(meta: {{ $requisito['meta'] }})</span>
                                @endif
                            </span>
                            @if ($requisito['tipo'] === 'contador')
                                <x-progresso.badge :color="$requisito['valor'] >= ($requisito['meta'] ?? 1) ? 'success' : 'gray'">
                                    {{ $requisito['valor'] }}{{ isset($requisito['meta']) ? ' / '.$requisito['meta'] : '' }}
                                </x-progresso.badge>
                            @else
                                <x-progresso.badge :color="$requisito['valor'] ? 'success' : 'gray'">
                                    {{ $requisito['valor'] ? 'Concluído' : 'Pendente' }}
                                </x-progresso.badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
