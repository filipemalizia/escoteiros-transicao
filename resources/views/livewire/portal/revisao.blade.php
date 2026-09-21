<div>
    <h1 class="text-xl font-bold text-gray-950 dark:text-white">Revisão</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Itens enviados, aguardando confirmação de um chefe.</p>

    @if (empty($itens))
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Nada aguardando revisão no momento.</p>
    @else
        <ul class="mt-6 space-y-3">
            @foreach ($itens as $item)
                <li class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                    <div class="flex items-start gap-3">
                        <x-progresso.status-icone :concluido="false" :solicitado="true" class="mt-0.5" />
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['texto'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['contexto'] }}</div>
                            @if ($item['observacao'])
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['observacao'] }}</p>
                            @endif
                            @if ($item['solicitado_em'])
                                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    Enviado em {{ $item['solicitado_em']->format('d/m/Y') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
