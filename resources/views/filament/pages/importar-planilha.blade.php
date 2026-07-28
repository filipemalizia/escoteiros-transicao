<x-filament-panels::page>
    <form wire:submit="importar" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Importar
        </x-filament::button>
    </form>

    @if ($resumo)
        <x-filament::section heading="Resumo da importação" class="mt-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Grupos criados</div>
                    <div class="text-2xl font-semibold">{{ $resumo['gruposCriados'] }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Subgrupos criados</div>
                    <div class="text-2xl font-semibold">{{ $resumo['subgruposCriados'] }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Itens criados</div>
                    <div class="text-2xl font-semibold text-success-600">{{ $resumo['itensCriados'] }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Itens ignorados</div>
                    <div class="text-2xl font-semibold text-danger-600">{{ $resumo['itensIgnorados'] }}</div>
                </div>
            </div>

            @if (! empty($resumo['erros']))
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-danger-600">Linhas ignoradas</h3>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($resumo['erros'] as $erro)
                            <li>Linha {{ $erro['linha'] }}: {{ $erro['motivo'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($resumo['avisos']))
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-warning-600">Avisos</h3>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($resumo['avisos'] as $aviso)
                            <li>Linha {{ $aviso['linha'] }}: {{ $aviso['motivo'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
