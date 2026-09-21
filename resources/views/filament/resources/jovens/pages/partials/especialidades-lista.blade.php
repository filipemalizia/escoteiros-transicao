@php
    /**
     * @var \Illuminate\Support\Collection $especialidades
     * @var array<int, \App\Models\ProgressoEspecialidade> $progressoEspecialidadeMap
     * @var \Closure $aguardandoAvaliacao
     * @var \Closure $corStatus
     * @var string $mensagemVazio
     */
@endphp

<div class="divide-y divide-gray-100 dark:divide-white/10">
    @forelse ($especialidades as $especialidade)
        @php
            $statusEspecialidade = $this->statusEspecialidade($especialidade);
            $itensDaEspecialidade = $especialidade->grupos->flatMap->itens;
            $avaliacoesPendentesEspecialidade = $itensDaEspecialidade->filter(fn ($item) => $aguardandoAvaliacao($progressoEspecialidadeMap[$item->id] ?? null))->count();
            $badgeStatus = $statusEspecialidade['nivel_atingido'] !== null
                ? ($statusEspecialidade['nivel_atingido'] > 0 ? "Nível {$statusEspecialidade['nivel_atingido']}" : 'Pendente')
                : $statusEspecialidade['status'];
        @endphp
        <x-progresso.accordion
            :id="'especialidade-'.$especialidade->id"
            :heading="$especialidade->nome"
            :description="$especialidade->descricao"
            :status="$badgeStatus"
            :status-color="$corStatus($statusEspecialidade['status'])"
            :avaliacoes-pendentes="$avaliacoesPendentesEspecialidade"
        >
            @if ($statusEspecialidade['status'] === 'Concluído' || ($statusEspecialidade['nivel_atingido'] ?? 0) >= 1)
                <x-slot:acoes>
                    <x-progresso.botao-compartilhar
                        :tipo="$especialidade->tipo === 'Insígnia' ? 'insignia' : 'especialidade'"
                        :titulo="$especialidade->nome"
                        :imagem-url="$especialidade->dataUriImagemParaNivel($statusEspecialidade['nivel_atingido'] ?? 1)"
                        :nivel="$statusEspecialidade['nivel_atingido']"
                        :jovem="$this->getRecord()"
                    />
                </x-slot:acoes>
            @endif

            @foreach ($especialidade->grupos as $grupo)
                <div class="mb-3">
                    @if ($especialidade->estrutura !== 'itens_niveis')
                        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                            {{ $grupo->chave }}
                            @if ($grupo->quantidade_minima)
                                (mínimo {{ $grupo->quantidade_minima }} de {{ $grupo->itens->count() }})
                            @endif
                        </div>
                    @endif
                    @if ($grupo->mensagem_regras)
                        <p class="mb-2 text-xs italic text-gray-500 dark:text-gray-400">{{ $grupo->mensagem_regras }}</p>
                    @endif
                    <ul class="space-y-2">
                        @foreach ($grupo->itens as $item)
                            @php
                                $registroEspecialidade = $progressoEspecialidadeMap[$item->id] ?? null;
                                $concluidoEspecialidade = (bool) ($registroEspecialidade?->concluido);
                                $solicitadoEspecialidade = (bool) ($registroEspecialidade?->solicitado_pelo_jovem);
                            @endphp
                            <li wire:key="item-especialidade-{{ $item->id }}-{{ $concluidoEspecialidade ? 1 : 0 }}" class="flex flex-wrap items-start gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                <label class="-mx-3 flex flex-1 cursor-pointer items-start gap-3 px-3">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleEspecialidade({{ $item->id }})"
                                        @checked($concluidoEspecialidade)
                                        class="mt-0.5 h-6 w-6 shrink-0 rounded border-gray-300 accent-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-1 dark:border-gray-600 dark:focus:ring-offset-gray-900"
                                    />
                                    <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                        <span>{{ $item->texto }}</span>
                                        @if ($solicitadoEspecialidade && ! $concluidoEspecialidade)
                                            <x-filament::badge color="warning" size="sm">
                                                Aguardando avaliação
                                            </x-filament::badge>
                                            @if ($registroEspecialidade?->observacao_jovem)
                                                <span class="block w-full text-xs italic text-gray-500 dark:text-gray-400">
                                                    "{{ $registroEspecialidade->observacao_jovem }}"
                                                </span>
                                            @endif
                                        @endif
                                        @if ($concluidoEspecialidade && $registroEspecialidade?->data_conclusao)
                                            <span class="block w-full text-xs text-gray-400 dark:text-gray-500">
                                                Concluído em {{ $registroEspecialidade->data_conclusao->format('d/m/Y') }}
                                                @if ($registroEspecialidade->registradoPor)
                                                    por {{ $registroEspecialidade->registradoPor->name }}
                                                @endif
                                            </span>
                                        @endif
                                    </span>
                                </label>
                                @if ($solicitadoEspecialidade && ! $concluidoEspecialidade)
                                    <button type="button" wire:click="abrirAvaliacao('especialidade', {{ $item->id }})" class="shrink-0 rounded-lg bg-warning-600 px-2 py-1 text-xs font-medium text-white hover:bg-warning-500">
                                        Avaliação
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </x-progresso.accordion>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $mensagemVazio }}
        </p>
    @endforelse
</div>
