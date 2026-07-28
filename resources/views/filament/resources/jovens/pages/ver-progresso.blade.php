@php
    $progressoAntigoMap = $this->getProgressoAntigoMap();
    $progressoNovoMap = $this->getProgressoNovoMap();
    $percentualAntigo = $this->getPercentualAntigo();
    $percentualNovo = $this->getPercentualNovo();

    $corStatus = fn (string $status) => match ($status) {
        'Concluído' => 'success',
        'Parcial' => 'warning',
        default => 'gray',
    };
@endphp

<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Programa Antigo</div>
            <div class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $percentualAntigo['percentual'] }}%</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $percentualAntigo['concluidas'] }} de {{ $percentualAntigo['total'] }} competências concluídas
            </div>
            <div class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                Etapa atual: {{ $this->getEtapaAntigo() }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Programa Novo</div>
            <div class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $percentualNovo['percentual'] }}%</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $percentualNovo['concluidos'] }} de {{ $percentualNovo['total'] }} blocos concluídos
            </div>
            <div class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                Etapa atual: {{ $this->getEtapaNovo() }}
            </div>
        </x-filament::section>
    </div>

    <x-filament::tabs label="Programa" class="mt-8">
        <x-filament::tabs.item
            :active="$abaAtiva === 'antigo'"
            wire:click="$set('abaAtiva', 'antigo')"
        >
            Programa Antigo
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$abaAtiva === 'novo'"
            wire:click="$set('abaAtiva', 'novo')"
        >
            Programa Novo
        </x-filament::tabs.item>
    </x-filament::tabs>

    @if ($abaAtiva === 'antigo')
        <div class="mt-6 space-y-6">
            @php
                $elegivelAntigo = $this->getElegivelReconhecimentoAntigo();
                $nomeReconhecimentoAntigo = $this->getNomeReconhecimentoAntigo();
                $requisitosComplementaresAntigo = $this->getRequisitosComplementaresAntigo();
            @endphp

            <x-filament::section :heading="$elegivelAntigo ? '🎉 Elegível ao ' . $nomeReconhecimentoAntigo . '!' : 'Reconhecimento: ' . $nomeReconhecimentoAntigo">
                @php $resumoAntigo = $this->getResumoAntigo(); @endphp

                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Itens concluídos: {{ $resumoAntigo['concluidos'] }} de {{ $resumoAntigo['total'] }}
                    ({{ $resumoAntigo['percentual'] }}%)
                </p>

                @if ($elegivelAntigo)
                    <p class="mt-2 text-sm text-success-600 dark:text-success-400">
                        Todos os itens e requisitos complementares foram atendidos.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ainda não elegível.</p>
                @endif

                @include('filament.resources.jovens.pages.partials.requisitos-complementares', ['requisitos' => $requisitosComplementaresAntigo, 'prefixo' => 'antigo'])
            </x-filament::section>

            @forelse ($this->getAreasAntigas() as $area)
                <x-filament::section :heading="$area->nome">
                    <div class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($area->competencias as $competencia)
                            @php $statusCompetencia = $this->statusCompetencia($competencia); @endphp
                            <div class="py-5 first:pt-0 last:pb-0">
                                <h4 class="mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-semibold text-gray-950 dark:text-white">
                                    <x-filament::badge :color="$corStatus($statusCompetencia['status'])" size="sm">
                                        {{ $statusCompetencia['status'] }}
                                    </x-filament::badge>
                                    <span>{{ $competencia->descricao }}</span>
                                    <span class="font-normal text-gray-500 dark:text-gray-400">
                                        ({{ $statusCompetencia['itens_concluidos'] }}/{{ $statusCompetencia['itens_necessarios'] }})
                                    </span>
                                </h4>

                                <ul class="space-y-1">
                                    @foreach ($competencia->itens as $item)
                                        @php
                                            $registro = $progressoAntigoMap[$item->id] ?? null;
                                            $marcadoDireto = (bool) ($registro?->concluido);
                                            $concluidoGeral = $this->itemAntigoConcluido($item);
                                        @endphp
                                        <li wire:key="item-antigo-{{ $item->id }}-{{ $concluidoGeral ? 1 : 0 }}">
                                            <label class="-mx-3 flex cursor-pointer items-start gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                                <input
                                                    type="checkbox"
                                                    wire:click="toggleAntigo({{ $item->id }})"
                                                    @checked($concluidoGeral)
                                                    class="mt-0.5 h-6 w-6 shrink-0 rounded border-gray-300 accent-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-1 dark:border-gray-600 dark:focus:ring-offset-gray-900"
                                                />
                                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item->codigo }}</span>
                                                    @if ($item->etapa)
                                                        <x-filament::badge color="primary" size="sm">
                                                            {{ $item->etapa }}
                                                        </x-filament::badge>
                                                    @endif
                                                    <span>{{ $item->descricao }}</span>
                                                    @if ($concluidoGeral && ! $marcadoDireto)
                                                        <x-filament::badge color="info" size="sm" icon="heroicon-o-link">
                                                            via equivalência
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
                                                </span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nenhuma Área de Desenvolvimento cadastrada para o ramo deste jovem.
                </p>
            @endforelse
        </div>
    @else
        <div class="mt-6 space-y-6">
            @php
                $elegivelNovo = $this->getElegivelReconhecimentoNovo();
                $nomeReconhecimentoNovo = $this->getNomeReconhecimentoNovo();
                $requisitosComplementaresNovo = $this->getRequisitosComplementaresNovo();
            @endphp

            <x-filament::section :heading="$elegivelNovo ? '🎉 Elegível ao ' . $nomeReconhecimentoNovo . '!' : 'Reconhecimento: ' . $nomeReconhecimentoNovo">
                @php $resumoNovo = $this->getResumoNovo(); @endphp

                <ul class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <li>Blocos concluídos: {{ $resumoNovo['blocos_concluidos'] }} de {{ $resumoNovo['blocos_total'] }}</li>
                    <li>Ações Obrigatórias concluídas: {{ $resumoNovo['obrigatorias_concluidas'] }} de {{ $resumoNovo['obrigatorias_total'] }}</li>
                    <li>Ações Variáveis (dentro do mínimo exigido): {{ $resumoNovo['variaveis_atingidas'] }} de {{ $resumoNovo['variaveis_minimas_total'] }}</li>
                </ul>

                @if ($elegivelNovo)
                    <p class="mt-2 text-sm text-success-600 dark:text-success-400">
                        Todos os 18 blocos e requisitos complementares foram atendidos.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ainda não elegível.</p>
                @endif

                @include('filament.resources.jovens.pages.partials.requisitos-complementares', ['requisitos' => $requisitosComplementaresNovo, 'prefixo' => 'novo'])
            </x-filament::section>

            @forelse ($this->getEixosNovos() as $eixo)
                <x-filament::section :heading="$eixo->nome">
                    <div class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($eixo->blocos as $bloco)
                            @php $statusBloco = $this->statusBloco($bloco); @endphp
                            <div class="py-5 first:pt-0 last:pb-0">
                                <h4 class="mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-semibold text-gray-950 dark:text-white">
                                    <x-filament::badge :color="$corStatus($statusBloco['status'])" size="sm">
                                        {{ $statusBloco['status'] }}
                                    </x-filament::badge>
                                    <span>{{ $bloco->titulo }}</span>
                                    @if ($bloco->descricao)
                                        <span class="font-normal text-gray-500 dark:text-gray-400">— {{ $bloco->descricao }}</span>
                                    @endif
                                </h4>

                                <ul class="space-y-1">
                                    @foreach ($bloco->itens as $item)
                                        @php
                                            $registro = $progressoNovoMap[$item->id] ?? null;
                                            $marcadoDireto = (bool) ($registro?->concluido);
                                            $concluidoGeral = $this->itemNovoConcluido($item);
                                        @endphp
                                        <li wire:key="item-novo-{{ $item->id }}-{{ $concluidoGeral ? 1 : 0 }}">
                                            <label class="-mx-3 flex cursor-pointer items-start gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
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
                                                    @if ($marcadoDireto && $registro?->data_conclusao)
                                                        <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                                            Concluído em {{ $registro->data_conclusao->format('d/m/Y') }}
                                                            @if ($registro->registradoPor)
                                                                por {{ $registro->registradoPor->name }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nenhum Eixo cadastrado para o ramo deste jovem.
                </p>
            @endforelse

            <x-filament::section heading="Pendências">
                @php
                    $pendencias = $this->getPendenciasNovo();
                @endphp

                <div class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($pendencias as $pendencia)
                        <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <x-filament::badge :color="$corStatus($pendencia['status'])" size="sm">
                                {{ $pendencia['status'] }}
                            </x-filament::badge>
                            <div>
                                <div class="font-medium text-gray-950 dark:text-white">{{ $pendencia['bloco']->titulo }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $pendencia['detalhe'] }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Nenhuma pendência — todos os blocos do ramo estão concluídos.
                        </p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
