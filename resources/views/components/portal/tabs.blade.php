@props(['ativa' => null])

@php
    $jovemAtual = app(\App\Services\Portal\SessaoJovemService::class)->jovemAutenticado();
    $contagemRevisao = $jovemAtual ? app(\App\Services\StatusProgressaoService::class)->contagemAguardandoRevisao($jovemAtual) : 0;

    $itens = [
        ['aba' => 'timeline', 'rota' => 'portal.timeline', 'label' => 'Linha do Tempo', 'icone' => 'heroicon-o-presentation-chart-line'],
        ['aba' => 'inicio', 'rota' => 'portal.progresso', 'label' => 'Início', 'icone' => 'heroicon-o-home'],
        ['aba' => 'revisao', 'rota' => 'portal.revisao', 'label' => 'Revisão', 'icone' => 'heroicon-o-clipboard-document-check', 'contagem' => $contagemRevisao],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white sm:static sm:inset-auto sm:border-b sm:border-t-0 dark:border-white/10 dark:bg-gray-900">
    <div class="mx-auto flex max-w-3xl items-center justify-around px-4 sm:justify-start sm:gap-6 sm:px-6 lg:px-8">
        <div class="hidden shrink-0 items-center gap-2 sm:flex">
            <img src="{{ asset('images/logo.svg') }}" alt="" class="h-8 w-8">
            @include('filament.components.brand-nome')
        </div>

        @foreach ($itens as $item)
            @php $estaAtiva = $ativa === $item['aba']; @endphp
            <a
                href="{{ route($item['rota']) }}"
                class="relative flex flex-1 flex-col items-center gap-1 py-2 text-xs font-medium sm:flex-none sm:flex-row sm:gap-2 sm:rounded-t-lg sm:px-3 sm:py-3 sm:text-sm {{ $estaAtiva ? 'font-semibold text-primary-600 dark:text-primary-400 sm:bg-primary-50 dark:sm:bg-primary-950/40' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' }}"
            >
                <span class="relative">
                    <x-filament::icon :icon="$item['icone']" class="h-5 w-5" />
                    @if (($item['contagem'] ?? 0) > 0)
                        <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white">
                            {{ $item['contagem'] }}
                        </span>
                    @endif
                </span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
