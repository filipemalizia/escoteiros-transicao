@props([
    'id',
    'heading',
    'description' => null,
    'status' => null,
    'statusColor' => 'gray',
    'meta' => null,
    'pendencia' => null,
    'avaliacoesPendentes' => 0,
])

{{--
    Acordeão fechado por padrão, usado tanto na tela de progresso do painel
    quanto no portal público do jovem. Não usa o `x-collapse` do Alpine
    (plugin opcional) porque o portal público não carrega o bundle de JS do
    Filament — só `x-show`, pra funcionar igual nos dois contextos.
--}}
<div
    x-data="{ open: false }"
    id="{{ $id }}"
    {{
        $attributes->class([
            'py-6 first:pt-0 last:pb-0',
            'border-l-4 border-amber-400 pl-3 -ml-3 dark:border-amber-500' => $avaliacoesPendentes > 0,
        ])
    }}
>
    <button type="button" x-on:click="open = ! open" class="flex w-full items-start justify-between gap-3 text-left">
        <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
            @if ($status)
                <x-progresso.badge :color="$statusColor">{{ $status }}</x-progresso.badge>
            @endif
            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $heading }}</span>
            @if ($meta)
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ $meta }}</span>
            @endif
            @if ($avaliacoesPendentes > 0)
                <x-progresso.badge color="warning">
                    🔔 {{ $avaliacoesPendentes }} {{ $avaliacoesPendentes === 1 ? 'item aguardando avaliação' : 'itens aguardando avaliação' }}
                </x-progresso.badge>
            @endif
        </span>
        <x-filament::icon
            icon="heroicon-o-chevron-down"
            x-bind:class="open ? 'rotate-180' : ''"
            class="mt-0.5 h-5 w-5 shrink-0 text-gray-400 transition-transform"
        />
    </button>

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
