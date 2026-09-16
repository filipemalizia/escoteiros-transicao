@php
    $jovem = $this->jovem();
    $progressoNovoMap = $this->getProgressoNovoMap();
    $progressoPersonalizadoMap = $this->getProgressoPersonalizadoMap();
    $percentualNovo = $this->getPercentualNovo();
    $resumoNovo = $this->getResumoNovo();
    $elegivelNovo = $this->getElegivelReconhecimentoNovo();
    $nomeReconhecimentoNovo = $this->getNomeReconhecimentoNovo();
    $requisitosComplementaresNovo = $this->getRequisitosComplementaresNovo();

    $corStatus = fn (string $status) => match ($status) {
        'Concluído' => 'success',
        'Parcial' => 'warning',
        default => 'gray',
    };

    $corTipoAcao = fn (string $tipo) => match ($tipo) {
        'Obrigatória' => 'danger',
        'Variável' => 'warning',
        'Substitutiva' => 'info',
        default => 'gray',
    };

    $detalhesPendenciaNovo = collect($this->getPendenciasNovo())->keyBy(fn (array $p) => $p['bloco']->id);
@endphp

<div>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-950 dark:text-white">Olá, {{ $jovem->nome }}</h1>
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
        <div class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $percentualNovo['percentual'] }}%</div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $percentualNovo['concluidos'] }} de {{ $percentualNovo['total'] }} blocos concluídos
        </div>
        <div class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-200">
            Etapa atual: {{ $this->getEtapaNovo() }}
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <h2 class="font-semibold text-gray-950 dark:text-white">
            {{ $elegivelNovo ? '🎉 Elegível ao '.$nomeReconhecimentoNovo.'!' : 'Reconhecimento: '.$nomeReconhecimentoNovo }}
        </h2>

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

    <div class="mt-8 space-y-6">
        @forelse ($this->getEixosNovos() as $eixo)
            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <h2 class="mb-2 font-semibold text-gray-950 dark:text-white">{{ $eixo->nome }}</h2>
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
                        >
                            <ul class="space-y-2">
                                @foreach ($bloco->itens as $item)
                                    @php
                                        $registro = $progressoNovoMap[$item->id] ?? null;
                                        $marcadoDireto = (bool) ($registro?->concluido);
                                        $concluidoGeral = $this->itemNovoConcluido($item);
                                        $solicitado = (bool) ($registro?->solicitado_pelo_jovem);
                                    @endphp
                                    <li class="flex flex-wrap items-start gap-3 rounded-lg px-1 py-2">
                                        <x-progresso.badge :color="$concluidoGeral ? 'success' : ($solicitado ? 'warning' : 'gray')">
                                            {{ $concluidoGeral ? 'Concluído' : ($solicitado ? 'Aguardando confirmação' : 'Pendente') }}
                                        </x-progresso.badge>
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
                                        </span>
                                        @if (! $concluidoGeral && ! $solicitado)
                                            <button
                                                type="button"
                                                wire:click="solicitarNovo({{ $item->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="solicitarNovo({{ $item->id }})"
                                                class="shrink-0 rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-wait disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                                            >
                                                <span wire:loading.remove wire:target="solicitarNovo({{ $item->id }})">Marcar como feito</span>
                                                <span wire:loading wire:target="solicitarNovo({{ $item->id }})" class="inline-flex items-center gap-1">
                                                    <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    Enviando...
                                                </span>
                                            </button>
                                        @endif
                                    </li>
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
                                    <ul class="space-y-2">
                                        @foreach ($itensPersonalizados as $itemPersonalizado)
                                            @php
                                                $registroPersonalizado = $progressoPersonalizadoMap[$itemPersonalizado->id] ?? null;
                                                $concluidoPersonalizado = (bool) ($registroPersonalizado?->concluido);
                                                $solicitadoPersonalizado = (bool) ($registroPersonalizado?->solicitado_pelo_jovem);
                                            @endphp
                                            <li class="flex flex-wrap items-start gap-3 rounded-lg px-1 py-2">
                                                <x-progresso.badge :color="$concluidoPersonalizado ? 'success' : ($solicitadoPersonalizado ? 'warning' : 'gray')">
                                                    {{ $concluidoPersonalizado ? 'Concluído' : ($solicitadoPersonalizado ? 'Aguardando confirmação' : 'Pendente') }}
                                                </x-progresso.badge>
                                                <span class="flex flex-1 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                                    <x-progresso.badge color="warning">Personalizado</x-progresso.badge>
                                                    <span>{{ $itemPersonalizado->descricao }}</span>
                                                    @if ($concluidoPersonalizado && $registroPersonalizado?->data_conclusao)
                                                        <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                                            Concluído em {{ $registroPersonalizado->data_conclusao->format('d/m/Y') }}
                                                        </span>
                                                    @endif
                                                </span>
                                                @if (! $concluidoPersonalizado && ! $solicitadoPersonalizado)
                                                    <button
                                                        type="button"
                                                        wire:click="solicitarItemPersonalizado({{ $itemPersonalizado->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="solicitarItemPersonalizado({{ $itemPersonalizado->id }})"
                                                        class="shrink-0 rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-wait disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                                                    >
                                                        <span wire:loading.remove wire:target="solicitarItemPersonalizado({{ $itemPersonalizado->id }})">Marcar como feito</span>
                                                        <span wire:loading wire:target="solicitarItemPersonalizado({{ $itemPersonalizado->id }})" class="inline-flex items-center gap-1">
                                                            <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                            </svg>
                                                            Enviando...
                                                        </span>
                                                    </button>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </x-progresso.accordion>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum Eixo cadastrado para o seu ramo.</p>
        @endforelse
    </div>
</div>
