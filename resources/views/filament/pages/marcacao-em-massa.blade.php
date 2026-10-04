<x-filament-panels::page>
    <form wire:submit="marcar" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">
            Marcar como Concluído
        </x-filament::button>
    </form>
</x-filament-panels::page>
