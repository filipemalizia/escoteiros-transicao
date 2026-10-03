@props([
    'id',
    'heading',
    'description' => null,
    'status' => null,
    'statusColor' => 'gray',
    'meta' => null,
    'headingHtml' => null,
    'pendencia' => null,
    'avaliacoesPendentes' => 0,
    'queroFazer' => false,
    'comImagem' => false,
    'imagemUrl' => null,
    'imagemColorida' => false,
    'abertoPorPadrao' => false,
])

{{--
    Acordeão fechado por padrão (a menos que `abertoPorPadrao` diga o
    contrário — usado por deep links de busca), usado tanto na tela de
    progresso do painel quanto no portal público do jovem. Não usa o
    `x-collapse` do Alpine (plugin opcional) porque o portal público não
    carrega o bundle de JS do Filament — só `x-show`, pra funcionar igual
    nos dois contextos.

    O listener de `abrir-acordeao` existe pro caminho do adulto: como a
    tela inteira já está renderizada (não há navegação entre páginas como
    no portal), abrir um bloco específico a partir do modal "Buscar em
    tudo" precisa de um evento de browser em vez de só um query param.
--}}
<div
    x-data="{ open: {{ $abertoPorPadrao ? 'true' : 'false' }} }"
    x-init="if (open) { $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' })) }"
    x-on:abrir-acordeao.window="if ($event.detail.id === '{{ $id }}') { open = true; $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' })) }"
    id="{{ $id }}"
    {{
        $attributes->class([
            'py-6 first:pt-0 last:pb-0',
            'border-l-4 border-amber-400 pl-3 -ml-3 dark:border-amber-500' => $avaliacoesPendentes > 0,
        ])
    }}
>
    <div class="flex w-full items-start justify-between gap-3">
        <button type="button" x-on:click="open = ! open" class="flex flex-1 items-start gap-3 text-left">
            <span class="flex flex-1 items-center gap-3">
                @if ($comImagem)
                    <x-progresso.imagem-badge :url="$imagemUrl" :colorida="$imagemColorida" :alt="$heading" size="h-12 w-12" />
                @endif

                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    @if ($status)
                        <x-progresso.badge :color="$statusColor">{{ $status }}</x-progresso.badge>
                    @endif
                    <span class="text-sm font-semibold text-gray-950 dark:text-white">
                        @if ($headingHtml)
                            {!! $headingHtml !!}
                        @else
                            {{ $heading }}
                        @endif
                    </span>
                    <x-progresso.indicador-quero-fazer :marcado="$queroFazer" />
                    @if ($meta)
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ $meta }}</span>
                    @endif
                    @if ($avaliacoesPendentes > 0)
                        <x-progresso.badge color="warning">
                            🔔 {{ $avaliacoesPendentes }} {{ $avaliacoesPendentes === 1 ? 'item aguardando avaliação' : 'itens aguardando avaliação' }}
                        </x-progresso.badge>
                    @endif
                </span>
            </span>
            <x-filament::icon
                icon="heroicon-o-chevron-down"
                x-bind:class="open ? 'rotate-180' : ''"
                class="mt-0.5 h-5 w-5 shrink-0 text-gray-400 transition-transform"
            />
        </button>

        @isset($acoes)
            <div class="shrink-0 pt-0.5">{{ $acoes }}</div>
        @endisset
    </div>

    @if ($description)
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @if ($pendencia)
        <p class="mt-2 text-sm text-amber-700 dark:text-amber-400">{{ $pendencia }}</p>
    @endif

    <div x-show="open" class="mt-5">
        {{ $slot }}
    </div>
</div>
