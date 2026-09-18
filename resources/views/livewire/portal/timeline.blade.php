<div>
    <h1 class="text-xl font-bold text-gray-950 dark:text-white">Linha do Tempo</h1>

    @if (empty($eventos))
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Nenhuma conquista ainda.</p>
    @else
        <ol class="relative mt-6 border-l border-gray-200 dark:border-white/10">
            @foreach ($eventos as $evento)
                <li class="mb-6 ml-6">
                    <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-600 ring-4 ring-white dark:ring-gray-900"></span>
                    <div class="flex items-center gap-3">
                        <x-progresso.imagem-badge
                            :url="$evento['imagem_url']"
                            :colorida="true"
                            :alt="$evento['titulo']"
                            size="h-12 w-12"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $evento['titulo'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $evento['subtitulo'] }}</div>
                            <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $evento['data']->format('d/m/Y') }}</div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        @if ($temMais)
            <div class="mt-2 text-center">
                <button
                    type="button"
                    wire:click="carregarMais"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Carregar mais
                </button>
            </div>
        @endif
    @endif
</div>
