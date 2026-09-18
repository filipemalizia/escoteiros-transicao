@props(['show' => false, 'heading' => null, 'wireCloseAction' => null, 'size' => 'sm:max-w-md', 'zIndex' => 'z-50'])

{{--
    Modal simples, sem plugin do Alpine (o portal público não carrega o
    bundle de JS do Filament) — a visibilidade é controlada pelo próprio
    Livewire (`show`), não por x-show. Mobile-first: vira uma folha que sobe
    da parte de baixo da tela em telas pequenas (mais fácil de usar com o
    polegar) e um card centralizado a partir do breakpoint `sm`. `zIndex`
    existe pra empilhar um modal menor (ex.: "enviar pra avaliação") por
    cima de um já aberto (ex.: detalhe de uma especialidade).
--}}
@if ($show)
    <div
        class="fixed inset-0 {{ $zIndex }} flex items-end justify-center bg-black/50 sm:items-center sm:p-4"
        @if ($wireCloseAction) wire:click.self="{{ $wireCloseAction }}" @endif
    >
        <div class="relative max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-4 shadow-xl dark:bg-gray-900 {{ $size }} sm:rounded-2xl">
            @if ($wireCloseAction)
                <button
                    type="button"
                    wire:click="{{ $wireCloseAction }}"
                    class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                </button>
            @endif

            @if ($heading)
                <h3 class="mb-3 pr-8 text-base font-semibold text-gray-950 dark:text-white">{{ $heading }}</h3>
            @endif

            {{ $slot }}
        </div>
    </div>
@endif
