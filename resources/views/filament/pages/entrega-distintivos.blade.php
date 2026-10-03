<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Distintivos (Especialidade, Insígnia, Etapa e Reconhecimento do Programa Novo) já conquistados, aguardando compra e entrega física.
        </p>
        <label class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
            <input
                type="checkbox"
                wire:model.live="mostrarEntregues"
                class="rounded border-gray-300 accent-primary-600 dark:border-gray-600"
            />
            Mostrar já entregues
        </label>
    </div>

    @php $pendencias = $this->getPendencias(); @endphp

    @if (empty($pendencias))
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nada por aqui.</p>
        </x-filament::section>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2">Jovem</th>
                        <th class="px-4 py-2">Distintivo</th>
                        <th class="px-4 py-2">Comprado</th>
                        <th class="px-4 py-2">Entregue</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($pendencias as $linha)
                        @php
                            $jovem = $linha['jovem'];
                            $entrega = $linha['entrega'];
                            $origemArgs = "'{$linha['origem']}', {$jovem->id}, ".json_encode($linha['chave']);
                        @endphp
                        <tr wire:key="entrega-{{ $linha['origem'] }}-{{ $jovem->id }}-{{ \Illuminate\Support\Str::slug($linha['chave']) }}">
                            <td class="px-4 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $jovem->nome }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $jovem->ramoAtual->nome }}</div>
                            </td>
                            <td class="px-4 py-2 align-top">
                                <div class="flex flex-wrap items-center gap-1">
                                    <x-filament::badge :color="match ($linha['tipo_label']) {
                                        'Insígnia' => 'info',
                                        'Reconhecimento' => 'warning',
                                        'Etapa' => 'primary',
                                        default => 'gray',
                                    }">
                                        {{ $linha['tipo_label'] }}
                                    </x-filament::badge>
                                    <span>{{ $linha['titulo'] }}</span>
                                    @if ($linha['nivel_atingido'])
                                        <span class="text-xs text-gray-500 dark:text-gray-400">(Nível {{ $linha['nivel_atingido'] }})</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-2 align-top">
                                @if ($entrega?->comprado_em)
                                    <x-filament::badge color="success">{{ $entrega->comprado_em->format('d/m/Y') }}</x-filament::badge>
                                @else
                                    <button
                                        type="button"
                                        wire:click="marcarCompradoHoje({{ $origemArgs }})"
                                        class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                                    >
                                        Marcar comprado
                                    </button>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-top">
                                @if ($entrega?->entregue_em)
                                    <x-filament::badge color="success">{{ $entrega->entregue_em->format('d/m/Y') }}</x-filament::badge>
                                @else
                                    <button
                                        type="button"
                                        wire:click="marcarEntregueHoje({{ $origemArgs }})"
                                        class="rounded-lg bg-primary-600 px-2 py-1 text-xs font-medium text-white hover:bg-primary-500"
                                    >
                                        Marcar entregue
                                    </button>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right align-top">
                                <button
                                    type="button"
                                    wire:click="abrirEdicaoEntrega({{ $origemArgs }}, {{ $entrega?->comprado_em ? "'".$entrega->comprado_em->toDateString()."'" : 'null' }}, {{ $entrega?->entregue_em ? "'".$entrega->entregue_em->toDateString()."'" : 'null' }})"
                                    title="Editar datas"
                                    class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                >
                                    <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-progresso.modal
        :show="(bool) $editandoOrigem"
        heading="Editar entrega do distintivo"
        wire-close-action="fecharEdicaoEntrega"
        size="sm:max-w-sm"
    >
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Comprado em</label>
        <input
            type="date"
            wire:model="editandoCompradoEm"
            class="mb-3 w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
        />

        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Entregue em</label>
        <input
            type="date"
            wire:model="editandoEntregueEm"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
        />

        <div class="mt-4 flex justify-end gap-2">
            <button
                type="button"
                wire:click="fecharEdicaoEntrega"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
            >
                Cancelar
            </button>
            <button
                type="button"
                wire:click="salvarEntrega"
                class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-500"
            >
                Salvar
            </button>
        </div>
    </x-progresso.modal>
</x-filament-panels::page>
