<x-filament-panels::page>
    <x-filament::section heading="Eixos">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @forelse ($this->getCategoriasEixo() as $categoria)
                <div class="flex flex-col items-center gap-2 rounded-xl border border-gray-200 p-3 text-center dark:border-white/10">
                    <x-progresso.imagem-badge
                        :url="$categoria->getFirstMediaUrl('imagem') ?: null"
                        :colorida="true"
                        :alt="$categoria->chave"
                        size="h-16 w-16"
                    />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $categoria->chave }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $categoria->eixosNovos->pluck('nome')->unique()->implode(', ') ?: 'Sem eixo vinculado' }}
                    </span>
                </div>
            @empty
                <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">Nenhuma imagem de eixo cadastrada.</p>
            @endforelse
        </div>
    </x-filament::section>

    <x-filament::section heading="Blocos" class="mt-6">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @forelse ($this->getCategoriasBloco() as $categoria)
                <div class="flex flex-col items-center gap-2 rounded-xl border border-gray-200 p-3 text-center dark:border-white/10">
                    <x-progresso.imagem-badge
                        :url="$categoria->getFirstMediaUrl('imagem') ?: null"
                        :colorida="true"
                        :alt="$categoria->chave"
                        size="h-16 w-16"
                    />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $categoria->chave }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $categoria->blocosNovos->pluck('titulo')->unique()->implode(', ') ?: 'Sem bloco vinculado' }}
                    </span>
                </div>
            @empty
                <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">Nenhuma imagem de bloco cadastrada.</p>
            @endforelse
        </div>
    </x-filament::section>

    <x-filament::section heading="Especialidades e Insígnias" class="mt-6">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @forelse ($this->getEspecialidadesDistintivos() as $especialidade)
                <div class="flex flex-col items-center gap-2 rounded-xl border border-gray-200 p-3 text-center dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <x-progresso.imagem-badge
                            :url="$especialidade->getFirstMediaUrl('imagem_nivel_1') ?: null"
                            :colorida="true"
                            :alt="$especialidade->nome.' - Nível 1'"
                            size="h-14 w-14"
                        />
                        @if ($especialidade->hasMedia('imagem_nivel_2'))
                            <x-progresso.imagem-badge
                                :url="$especialidade->getFirstMediaUrl('imagem_nivel_2')"
                                :colorida="true"
                                :alt="$especialidade->nome.' - Nível 2'"
                                size="h-14 w-14"
                            />
                        @endif
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $especialidade->nome }}</span>
                    <span class="inline-block rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                        {{ $especialidade->tipo }}
                    </span>
                </div>
            @empty
                <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">Nenhuma especialidade ou insígnia cadastrada.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
