<x-filament-panels::page>
    <form wire:submit="criar" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Criar Equivalência(s)
        </x-filament::button>
    </form>
</x-filament-panels::page>
