@props(['marcado' => false, 'wireClick', 'confirm' => null])

<button
    type="button"
    wire:click.stop="{{ $wireClick }}"
    @if ($confirm) wire:confirm="{{ $confirm }}" @endif
    title="{{ $marcado ? 'Remover da lista de quero fazer' : "Marcar como 'quero fazer'" }}"
    {{ $attributes->class(['shrink-0']) }}
>
    <x-filament::icon
        :icon="$marcado ? 'heroicon-s-star' : 'heroicon-o-star'"
        class="h-5 w-5 {{ $marcado ? 'text-amber-500' : 'text-gray-300 hover:text-gray-400 dark:text-gray-600 dark:hover:text-gray-500' }}"
    />
</button>
