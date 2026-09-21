<x-filament-panels::page>
    <form wire:submit="importar" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Importar
        </x-filament::button>
    </form>

    @if ($resumo)
        <x-filament::section heading="Resumo da importação" class="mt-6">
            <ul class="space-y-1 text-sm">
                <li>Criadas: {{ $resumo['criadas'] }}</li>
                <li>Atualizadas: {{ $resumo['atualizadas'] }}</li>
                <li>Adotadas de registros legados (mesmo nome, sem fonte_specialty_id): {{ $resumo['adotadas'] }}</li>
                <li>Eixos novos criados: {{ $resumo['eixos_novos_criados'] }}</li>
                @if ($resumo['dry_run'])
                    <li>Imagens que seriam baixadas: {{ $resumo['imagens_simuladas'] }}</li>
                @else
                    <li>Imagens baixadas: {{ $resumo['imagens_baixadas'] }}</li>
                @endif
                <li>Imagens já atualizadas (puladas): {{ $resumo['imagens_puladas'] }}</li>
                @if ($resumo['imagens_com_erro'] > 0)
                    <li class="text-amber-600 dark:text-amber-400">
                        Imagens com erro no download: {{ $resumo['imagens_com_erro'] }} (ver logs)
                    </li>
                @endif
            </ul>

            @if ($resumo['adotadas'] > 0)
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    Atenção: {{ $resumo['adotadas'] }} especialidade(s) já existiam (criadas pelo importador de
                    planilha) e foram enriquecidas em vez de duplicadas. Vale conferir manualmente algumas.
                </p>
            @endif

            @if (! empty($resumo['erros']))
                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10">
                    <div class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Especialidades com erro</div>
                    <ul class="divide-y divide-gray-100 text-sm dark:divide-white/10">
                        @foreach ($resumo['erros'] as $erro)
                            <li class="py-2">
                                <span class="font-medium">{{ $erro['nome'] }}</span> (ID {{ $erro['id'] }}): {{ $erro['motivo'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
