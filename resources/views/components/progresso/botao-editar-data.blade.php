@props(['wireClick'])

<button
    type="button"
    wire:click.stop="{{ $wireClick }}"
    title="Editar data de conclusão"
    class="inline-flex align-middle text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
>
    <x-filament::icon icon="heroicon-o-pencil-square" class="h-3.5 w-3.5" />
</button>
