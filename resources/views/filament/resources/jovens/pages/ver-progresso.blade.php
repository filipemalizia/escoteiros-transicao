@php
    $progressoAntigoMap = $this->getProgressoAntigoMap();
    $progressoNovoMap = $this->getProgressoNovoMap();
    $progressoPersonalizadoMap = $this->getProgressoPersonalizadoMap();
    $progressoEspecialidadeMap = $this->getProgressoEspecialidadeMap();
    $especialidadesFiltradas = $this->getEspecialidadesFiltradas();
    $insigniasFiltradas = $this->getInsigniasFiltradas();
    $avaliacoesPendentesEspecialidadesTotal = $this->getAvaliacoesPendentesPorTipo('Especialidade');
    $avaliacoesPendentesInsigniasTotal = $this->getAvaliacoesPendentesPorTipo('Insígnia');
    $itemParaAvaliar = $this->getItemParaAvaliar();
    $textoItemParaAvaliar = $avaliandoTipo === 'especialidade' ? $itemParaAvaliar?->texto : $itemParaAvaliar?->descricao;
    $registroParaAvaliar = match ($avaliandoTipo) {
        'novo' => $avaliandoItemId ? ($progressoNovoMap[$avaliandoItemId] ?? null) : null,
        'personalizado' => $avaliandoItemId ? ($progressoPersonalizadoMap[$avaliandoItemId] ?? null) : null,
        'especialidade' => $avaliandoItemId ? ($progressoEspecialidadeMap[$avaliandoItemId] ?? null) : null,
        default => null,
    };
    $percentualAntigo = $this->getPercentualAntigo();
    $percentualNovo = $this->getPercentualNovo();

    $corStatus = fn (string $status) => match ($status) {
        'Concluído' => 'success',
        'Parcial' => 'warning',
        default => 'gray',
    };

    $aguardandoAvaliacao = fn ($registro) => $registro && $registro->solicitado_pelo_jovem && ! $registro->concluido;

    $avaliacoesPendentesNovoTotal = collect($progressoNovoMap)->filter($aguardandoAvaliacao)->count();
    $avaliacoesPendentesAntigoTotal = collect($progressoAntigoMap)->filter($aguardandoAvaliacao)->count();
@endphp

<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
    </div>

    <x-filament::tabs label="Programa" class="mt-8">
        <x-filament::tabs.item
            :active="$abaAtiva === 'novo'"
            :badge="$avaliacoesPendentesNovoTotal > 0 ? $avaliacoesPendentesNovoTotal : null"
            badge-color="warning"
            wire:click="$set('abaAtiva', 'novo')"
        >
            Programa Novo
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$abaAtiva === 'especialidades'"
            :badge="$avaliacoesPendentesEspecialidadesTotal > 0 ? $avaliacoesPendentesEspecialidadesTotal : null"
            badge-color="warning"
            wire:click="$set('abaAtiva', 'especialidades')"
        >
            Especialidades
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$abaAtiva === 'insignias'"
            :badge="$avaliacoesPendentesInsigniasTotal > 0 ? $avaliacoesPendentesInsigniasTotal : null"
            badge-color="warning"
            wire:click="$set('abaAtiva', 'insignias')"
        >
            Insígnias
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$abaAtiva === 'antigo'"
            :badge="$avaliacoesPendentesAntigoTotal > 0 ? $avaliacoesPendentesAntigoTotal : null"
            badge-color="warning"
            wire:click="$set('abaAtiva', 'antigo')"
        >
            Programa Antigo
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-4 inline-flex gap-1 rounded-lg border border-gray-300 p-0.5 dark:border-gray-600">
        @foreach (['todos' => 'Todos', 'pendente' => 'Pendentes', 'concluido' => 'Concluídos'] as $valorFiltroStatus => $rotuloFiltroStatus)
            <button
                type="button"
                wire:click="$set('filtroStatusItens', '{{ $valorFiltroStatus }}')"
                @class([
                    'rounded-md px-3 py-1 text-xs font-medium transition-colors',
                    'bg-primary-600 text-white' => $filtroStatusItens === $valorFiltroStatus,
                    'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' => $filtroStatusItens !== $valorFiltroStatus,
                ])
            >
                {{ $rotuloFiltroStatus }}
            </button>
        @endforeach
    </div>

    @if ($abaAtiva === 'novo')
        <div class="mt-6 space-y-6">
            @if ($avaliacoesPendentesNovoTotal > 0)
                <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-400">
                    🔔 {{ $avaliacoesPendentesNovoTotal }} {{ $avaliacoesPendentesNovoTotal === 1 ? 'item aguardando' : 'itens aguardando' }} sua avaliação - abra o bloco marcado abaixo pra confirmar ou rejeitar.
                </div>
            @endif

            @php
                $elegivelNovo = $this->getElegivelReconhecimentoNovo();
                $nomeReconhecimentoNovo = $this->getNomeReconhecimentoNovo();
                $requisitosComplementaresNovo = $this->getRequisitosComplementaresNovo();
            @endphp

            <x-filament::section :heading="$elegivelNovo ? '🎉 Elegível ao ' . $nomeReconhecimentoNovo . '!' : 'Reconhecimento: ' . $nomeReconhecimentoNovo">
                @if ($elegivelNovo)
                    <x-slot name="afterHeader">
                        <x-progresso.botao-compartilhar
                            tipo="reconhecimento"
                            :titulo="$nomeReconhecimentoNovo"
                            :imagem-url="$this->getDataUriImagemReconhecimento()"
                            :jovem="$this->getRecord()"
                        />
                    </x-slot>
                @endif

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

            @php
                $detalhesPendenciaNovo = collect($this->getPendenciasNovo())->keyBy(fn (array $p) => $p['bloco']->id);
            @endphp

            @forelse ($this->getEixosNovos() as $eixo)
                <x-filament::section :heading="$eixo->nome">
                    @if ($this->eixoConcluido($eixo))
                        <x-slot name="afterHeader">
                            <x-progresso.botao-compartilhar
                                tipo="eixo"
                                :titulo="$eixo->nome"
                                :imagem-url="$eixo->categoriaImagem?->dataUriImagem()"
                                :jovem="$this->getRecord()"
                            />
                        </x-slot>
                    @endif

                    <div class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($eixo->blocos as $bloco)
                            @php
                                $statusBloco = $this->statusBloco($bloco);
                                $itensVisiveisBloco = $this->itensVisiveisDoBloco($bloco);
                                $avaliacoesPendentesBloco = $itensVisiveisBloco->filter(fn ($item) => $aguardandoAvaliacao($progressoNovoMap[$item->id] ?? null))->count();
                                $itensPersonalizados = $this->getItensPersonalizadosDoBloco($bloco);
                                $querendoFazer = fn ($registro) => $registro && $registro->marcado_para_fazer && ! $registro->concluido;
                                $queroFazerBloco = $itensVisiveisBloco->contains(fn ($item) => $querendoFazer($progressoNovoMap[$item->id] ?? null))
                                    || $itensPersonalizados->contains(fn ($item) => $querendoFazer($progressoPersonalizadoMap[$item->id] ?? null));
                            @endphp
                            <x-progresso.accordion
                                :id="'bloco-'.$bloco->id"
                                :heading="$bloco->titulo"
                                :description="$bloco->descricao"
                                :status="$statusBloco['status']"
                                :status-color="$corStatus($statusBloco['status'])"
                                :pendencia="$detalhesPendenciaNovo[$bloco->id]['detalhe'] ?? null"
                                :avaliacoes-pendentes="$avaliacoesPendentesBloco"
                                :quero-fazer="$queroFazerBloco"
                            >
                                @if ($statusBloco['status'] === 'Concluído')
                                    <x-slot:acoes>
                                        <x-progresso.botao-compartilhar
                                            tipo="bloco"
                                            :titulo="$bloco->titulo"
                                            :imagem-url="$bloco->categoriaImagem?->dataUriImagem()"
                                            :jovem="$this->getRecord()"
                                        />
                                    </x-slot:acoes>
                                @endif

                                @php
                                    $itensPrincipaisBloco = $itensVisiveisBloco->whereIn('tipo_acao', ['Obrigatória', 'Variável']);
                                    $itensSubstitutivasBloco = $itensVisiveisBloco->where('tipo_acao', 'Substitutiva');
                                @endphp

                                <ul class="space-y-2">
                                    @foreach ($itensPrincipaisBloco as $item)
                                        @continue(! $this->itemPassaFiltroStatus($this->itemNovoConcluido($item)))
                                        <x-progresso.item-novo-linha-admin
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
                                                @if ($itemAntigoVinculado && $this->itemPassaFiltroStatus($concluidoViaBloco))
                                                    <li wire:key="equivalencia-bloco-{{ $equivalenciaBloco->id }}-{{ $concluidoViaBloco ? 1 : 0 }}">
                                                        <label class="-mx-3 flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-gray-100 dark:hover:bg-white/10">
                                                            <input
                                                                type="checkbox"
                                                                wire:click="toggleAntigo({{ $itemAntigoVinculado->id }})"
                                                                @checked($concluidoViaBloco)
                                                                class="mt-0.5 h-6 w-6 shrink-0 rounded border-gray-300 accent-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-1 dark:border-gray-600 dark:focus:ring-offset-gray-900"
                                                            />
                                                            <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $itemAntigoVinculado->codigo }}</span>
                                                                <span>{{ $itemAntigoVinculado->descricao }}</span>
                                                            </span>
                                                        </label>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($itensPersonalizados->isNotEmpty())
                                    <div class="mt-3 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                                        <div class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            Itens personalizados deste jovem
                                        </div>
                                        <ul class="space-y-2">
                                            @foreach ($itensPersonalizados as $itemPersonalizado)
                                                @php
                                                    $registroPersonalizado = $progressoPersonalizadoMap[$itemPersonalizado->id] ?? null;
                                                    $marcadoDiretoPersonalizado = (bool) ($registroPersonalizado?->concluido);
                                                    $solicitadoPersonalizado = (bool) ($registroPersonalizado?->solicitado_pelo_jovem);
                                                    // Se o jovem já enviou isso pra avaliação, o checkbox não marca
                                                    // direto — abre o fluxo de Avaliação certo (mesmo botão de
                                                    // baixo), pra evitar que o chefe marque sem querer sem ver a
                                                    // observação do jovem antes.
                                                    $acaoCheckboxPersonalizado = $solicitadoPersonalizado ? "abrirAvaliacao('personalizado', {$itemPersonalizado->id})" : "toggleItemPersonalizado({$itemPersonalizado->id})";
                                                @endphp
                                                @continue(! $this->itemPassaFiltroStatus($marcadoDiretoPersonalizado))
                                                <li wire:key="item-personalizado-{{ $itemPersonalizado->id }}-{{ $marcadoDiretoPersonalizado ? 1 : 0 }}" class="flex flex-wrap items-start gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-gray-100 dark:hover:bg-white/10">
                                                    <label class="-mx-3 flex flex-1 cursor-pointer items-start gap-3 px-3">
                                                        <input
                                                            type="checkbox"
                                                            wire:click="{{ $acaoCheckboxPersonalizado }}"
                                                            @checked($marcadoDiretoPersonalizado)
                                                            class="mt-0.5 h-6 w-6 shrink-0 rounded border-gray-300 accent-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-1 dark:border-gray-600 dark:focus:ring-offset-gray-900"
                                                        />
                                                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                                            <x-filament::badge color="warning" size="sm">Personalizado</x-filament::badge>
                                                            <x-progresso.indicador-quero-fazer :marcado="! $marcadoDiretoPersonalizado && (bool) ($registroPersonalizado?->marcado_para_fazer)" />
                                                            <span>{{ $itemPersonalizado->descricao }}</span>
                                                            @if ($solicitadoPersonalizado && ! $marcadoDiretoPersonalizado)
                                                                <x-filament::badge color="warning" size="sm">
                                                                    Aguardando avaliação
                                                                </x-filament::badge>
                                                            @endif
                                                            @if ($marcadoDiretoPersonalizado && $registroPersonalizado?->data_conclusao)
                                                                <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                                                    Concluído em {{ $registroPersonalizado->data_conclusao->format('d/m/Y') }}
                                                                    @if ($registroPersonalizado->registradoPor)
                                                                        por {{ $registroPersonalizado->registradoPor->name }}
                                                                    @endif
                                                                    <x-progresso.botao-editar-data wire-click="abrirEdicaoData('personalizado', {{ $itemPersonalizado->id }}, '{{ $registroPersonalizado->data_conclusao->toDateString() }}')" />
                                                                </span>
                                                            @endif
                                                            @if ($registroPersonalizado?->observacao_jovem)
                                                                <span class="block w-full text-xs italic text-gray-500 dark:text-gray-400">
                                                                    "{{ $registroPersonalizado->observacao_jovem }}"
                                                                </span>
                                                            @endif
                                                            <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                                                Criado por {{ $itemPersonalizado->criadoPor?->name ?? 'usuário removido' }}
                                                            </span>
                                                        </span>
                                                    </label>
                                                    <div class="flex shrink-0 gap-2">
                                                        @if ($solicitadoPersonalizado && ! $marcadoDiretoPersonalizado)
                                                            <button type="button" wire:click="abrirAvaliacao('personalizado', {{ $itemPersonalizado->id }})" class="rounded-lg bg-warning-600 px-2 py-1 text-xs font-medium text-white hover:bg-warning-500">
                                                                Avaliação
                                                            </button>
                                                        @endif
                                                        <button
                                                            type="button"
                                                            wire:click="excluirItemPersonalizado({{ $itemPersonalizado->id }})"
                                                            wire:confirm="Excluir este item personalizado?"
                                                            title="Excluir item personalizado"
                                                            class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-white/10"
                                                        >
                                                            Excluir
                                                        </button>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <button
                                    type="button"
                                    wire:click="abrirFormularioItemPersonalizado({{ $bloco->id }})"
                                    class="mt-3 text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    + Item personalizado
                                </button>

                                @if ($itensSubstitutivasBloco->isNotEmpty())
                                    <ul class="mt-3 space-y-2">
                                        @foreach ($itensSubstitutivasBloco as $item)
                                            @continue(! $this->itemPassaFiltroStatus($this->itemNovoConcluido($item)))
                                            <x-progresso.item-novo-linha-admin
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
                </x-filament::section>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nenhum Eixo cadastrado para o ramo deste jovem.
                </p>
            @endforelse
        </div>
    @elseif ($abaAtiva === 'especialidades')
        <div class="mt-6 space-y-6">
            @if ($avaliacoesPendentesEspecialidadesTotal > 0)
                <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-400">
                    🔔 {{ $avaliacoesPendentesEspecialidadesTotal }} {{ $avaliacoesPendentesEspecialidadesTotal === 1 ? 'item aguardando' : 'itens aguardando' }} sua avaliação nas especialidades abaixo.
                </div>
            @endif

            <x-filament::section heading="Especialidades">
                <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass" class="mb-4 max-w-sm">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.300ms="buscaEspecialidades"
                        placeholder="Buscar Especialidade por nome, requisito ou item equivalente..."
                    />
                </x-filament::input.wrapper>

                @include('filament.resources.jovens.pages.partials.especialidades-lista', [
                    'especialidades' => $especialidadesFiltradas,
                    'busca' => $buscaEspecialidades,
                    'mensagemVazio' => filled($buscaEspecialidades)
                        ? 'Nenhuma Especialidade encontrada para essa busca.'
                        : 'Nenhuma Especialidade cadastrada para o ramo deste jovem.',
                ])
            </x-filament::section>
        </div>
    @elseif ($abaAtiva === 'insignias')
        <div class="mt-6 space-y-6">
            @if ($avaliacoesPendentesInsigniasTotal > 0)
                <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-400">
                    🔔 {{ $avaliacoesPendentesInsigniasTotal }} {{ $avaliacoesPendentesInsigniasTotal === 1 ? 'item aguardando' : 'itens aguardando' }} sua avaliação nas insígnias abaixo.
                </div>
            @endif

            <x-filament::section heading="Insígnias">
                <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass" class="mb-4 max-w-sm">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.300ms="buscaInsignias"
                        placeholder="Buscar Insígnia por nome, requisito ou item equivalente..."
                    />
                </x-filament::input.wrapper>

                @include('filament.resources.jovens.pages.partials.especialidades-lista', [
                    'especialidades' => $insigniasFiltradas,
                    'busca' => $buscaInsignias,
                    'mensagemVazio' => filled($buscaInsignias)
                        ? 'Nenhuma Insígnia encontrada para essa busca.'
                        : 'Nenhuma Insígnia cadastrada para o ramo deste jovem.',
                ])
            </x-filament::section>
        </div>
    @else
        <div class="mt-6 space-y-6">
            @if ($avaliacoesPendentesAntigoTotal > 0)
                <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-400">
                    🔔 {{ $avaliacoesPendentesAntigoTotal }} {{ $avaliacoesPendentesAntigoTotal === 1 ? 'item aguardando' : 'itens aguardando' }} sua avaliação - abra a competência marcada abaixo pra confirmar ou rejeitar.
                </div>
            @endif

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
                            @php
                                $statusCompetencia = $this->statusCompetencia($competencia);
                                $avaliacoesPendentesCompetencia = $competencia->itens->filter(fn ($item) => $aguardandoAvaliacao($progressoAntigoMap[$item->id] ?? null))->count();
                            @endphp
                            <x-progresso.accordion
                                :id="'competencia-'.$competencia->id"
                                :heading="$competencia->descricao"
                                :status="$statusCompetencia['status']"
                                :status-color="$corStatus($statusCompetencia['status'])"
                                :meta="'('.$statusCompetencia['itens_concluidos'].'/'.$statusCompetencia['itens_necessarios'].')'"
                                :avaliacoes-pendentes="$avaliacoesPendentesCompetencia"
                            >
                                <ul class="space-y-2">
                                    @foreach ($competencia->itens as $item)
                                        @php
                                            $registro = $progressoAntigoMap[$item->id] ?? null;
                                            $marcadoDireto = (bool) ($registro?->concluido);
                                            $concluidoGeral = $this->itemAntigoConcluido($item);
                                            $solicitado = (bool) ($registro?->solicitado_pelo_jovem);
                                        @endphp
                                        @continue(! $this->itemPassaFiltroStatus($concluidoGeral))
                                        <li wire:key="item-antigo-{{ $item->id }}-{{ $concluidoGeral ? 1 : 0 }}" class="flex flex-wrap items-start gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                            <label class="-mx-3 flex flex-1 cursor-pointer items-start gap-3 px-3">
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
                                                            <x-progresso.botao-editar-data wire-click="abrirEdicaoData('antigo', {{ $item->id }}, '{{ $registro->data_conclusao->toDateString() }}')" />
                                                        </span>
                                                    @endif
                                                </span>
                                            </label>
                                            @if ($solicitado && ! $concluidoGeral)
                                                <div class="flex shrink-0 gap-2">
                                                    <button type="button" wire:click="confirmarAntigo({{ $item->id }})" class="rounded-lg bg-success-600 px-2 py-1 text-xs font-medium text-white hover:bg-success-500">
                                                        Confirmar
                                                    </button>
                                                    <button type="button" wire:click="rejeitarAntigo({{ $item->id }})" class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/10">
                                                        Rejeitar
                                                    </button>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </x-progresso.accordion>
                        @endforeach
                    </div>
                </x-filament::section>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nenhuma Área de Desenvolvimento cadastrada para o ramo deste jovem.
                </p>
            @endforelse
        </div>
    @endif

    <x-progresso.modal
        :show="(bool) $itemParaAvaliar"
        heading="Avaliar solicitação"
        wire-close-action="fecharAvaliacao"
    >
        @if ($itemParaAvaliar)
            <p class="mb-3 text-sm text-gray-700 dark:text-gray-200">{{ $textoItemParaAvaliar }}</p>

            @if ($registroParaAvaliar?->observacao_jovem)
                <div class="mb-3 rounded-lg bg-gray-50 p-3 text-sm italic text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    "{{ $registroParaAvaliar->observacao_jovem }}"
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    wire:click="rejeitarAvaliacaoAtual"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Recusar
                </button>
                <button
                    type="button"
                    wire:click="confirmarAvaliacaoAtual"
                    class="rounded-lg bg-success-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-success-500"
                >
                    Aprovar
                </button>
            </div>
        @endif
    </x-progresso.modal>

    @if ($blocoParaNovoItemPersonalizado)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4"
            wire:click.self="fecharFormularioItemPersonalizado"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Novo item personalizado</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Entra como uma Ação Variável do bloco, só pra o(s) jovem(ns) selecionado(s).
                </p>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Descrição</label>
                    <textarea
                        wire:model="novoItemPersonalizadoDescricao"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
                    ></textarea>
                </div>

                @php $jovensDisponiveis = $this->getJovensDisponiveisParaItemPersonalizado(); @endphp
                @if ($jovensDisponiveis->isNotEmpty())
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Também aplicar para (opcional)
                        </label>
                        <select
                            wire:model="novoItemPersonalizadoOutrosJovensIds"
                            multiple
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
                        >
                            @foreach ($jovensDisponiveis as $outroJovem)
                                <option value="{{ $outroJovem->id }}">{{ $outroJovem->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="fecharFormularioItemPersonalizado"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="criarItemPersonalizado"
                        class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-500"
                    >
                        Criar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-progresso.modal
        :show="$buscaGeralAberta"
        heading="Buscar em tudo"
        wire-close-action="fecharBuscaGeral"
        size="sm:max-w-lg"
    >
        <input
            type="search"
            wire:model.live.debounce.300ms="buscaGeralTermo"
            autofocus
            placeholder="Buscar especialidade, insígnia ou item de progressão..."
            class="w-full rounded-lg border-gray-300 text-sm placeholder:text-gray-400 focus:border-gray-500 focus:ring-gray-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
        />

        @if (filled($buscaGeralTermo))
            @php
                $especialidadesDaBuscaGeral = $this->especialidadesDaBuscaGeral($buscaGeralTermo);
                $itensDeProgressaoDaBuscaGeral = $this->itensDeProgressaoDaBuscaGeral($buscaGeralTermo);
            @endphp

            @if ($especialidadesDaBuscaGeral->isEmpty() && $itensDeProgressaoDaBuscaGeral->isEmpty())
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Nada encontrado.</p>
            @endif

            @if ($especialidadesDaBuscaGeral->isNotEmpty())
                <div class="mt-4">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        Especialidades e Insígnias
                    </div>
                    <ul class="space-y-2">
                        @foreach ($especialidadesDaBuscaGeral as $especialidadeEncontrada)
                            <li>
                                <button
                                    type="button"
                                    wire:click="abrirResultadoEspecialidadeDaBuscaGeral('{{ $especialidadeEncontrada->tipo }}', @js($buscaGeralTermo))"
                                    class="flex w-full flex-wrap items-start gap-2 rounded-xl border border-gray-200 p-3 text-left text-sm hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                                >
                                    <x-filament::badge :color="$especialidadeEncontrada->tipo === 'Insígnia' ? 'info' : 'gray'">
                                        {{ $especialidadeEncontrada->tipo }}
                                    </x-filament::badge>
                                    <span class="min-w-0 flex-1"><x-progresso.destaque :texto="$especialidadeEncontrada->nome" :busca="$buscaGeralTermo" /></span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($itensDeProgressaoDaBuscaGeral->isNotEmpty())
                <div class="mt-4">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        Itens de Progressão
                    </div>
                    <ul class="space-y-2">
                        @foreach ($itensDeProgressaoDaBuscaGeral as $itemNovoEncontrado)
                            @php $concluidoNaBuscaGeral = $this->itemNovoConcluido($itemNovoEncontrado); @endphp
                            <li class="rounded-xl border border-gray-200 p-3 text-sm dark:border-white/10">
                                <div class="flex flex-wrap items-start gap-2">
                                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $itemNovoEncontrado->codigo }}</span>
                                    <x-filament::badge
                                        :color="match ($itemNovoEncontrado->tipo_acao) {
                                            'Obrigatória' => 'danger',
                                            'Variável' => 'warning',
                                            'Substitutiva' => 'info',
                                            default => 'gray',
                                        }"
                                    >
                                        {{ $itemNovoEncontrado->tipo_acao }}
                                    </x-filament::badge>
                                    @if ($concluidoNaBuscaGeral)
                                        <x-filament::badge color="success">Concluído</x-filament::badge>
                                    @endif
                                    <span class="min-w-0 flex-1"><x-progresso.destaque :texto="$itemNovoEncontrado->descricao" :busca="$buscaGeralTermo" /></span>
                                    <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                        {{ $itemNovoEncontrado->bloco->eixo->nome }} — {{ $itemNovoEncontrado->bloco->titulo }}
                                    </span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if (! $concluidoNaBuscaGeral)
                                        <button
                                            type="button"
                                            wire:click="marcarConcluidoDaBuscaGeral({{ $itemNovoEncontrado->id }})"
                                            class="rounded-lg bg-success-600 px-2 py-1 text-xs font-medium text-white hover:bg-success-500"
                                        >
                                            Marcar como concluído
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="abrirBlocoDaBuscaGeral({{ $itemNovoEncontrado->bloco_id }})"
                                        class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                                    >
                                        Abrir bloco
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </x-progresso.modal>

    <x-progresso.modal
        :show="(bool) $editandoDataTipo"
        heading="Editar data de conclusão"
        wire-close-action="fecharEdicaoData"
        size="sm:max-w-sm"
    >
        <input
            type="date"
            wire:model="editandoDataValor"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
        />

        <div class="mt-4 flex justify-end gap-2">
            <button
                type="button"
                wire:click="fecharEdicaoData"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
            >
                Cancelar
            </button>
            <button
                type="button"
                wire:click="salvarEdicaoData"
                class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-500"
            >
                Salvar
            </button>
        </div>
    </x-progresso.modal>

    <x-progresso.modal-compartilhar />
</x-filament-panels::page>
