@if (! empty($requisitos))
    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10">
        <div class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
            Requisitos Complementares
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach ($requisitos as $requisito)
                @php $valorChave = is_bool($requisito['valor']) ? ($requisito['valor'] ? 1 : 0) : $requisito['valor']; @endphp
                <li wire:key="requisito-{{ $prefixo }}-{{ $requisito['chave'] }}-{{ $valorChave }}" class="py-2 first:pt-0 last:pb-0">
                    @if ($requisito['tipo'] === 'contador')
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm text-gray-700 dark:text-gray-200">
                                {{ $requisito['label'] }}
                                @if (isset($requisito['meta']))
                                    <span class="text-gray-400 dark:text-gray-500">(meta: {{ $requisito['meta'] }})</span>
                                @endif
                            </span>
                            <input
                                type="number"
                                min="0"
                                value="{{ $requisito['valor'] }}"
                                wire:change="atualizarRequisitoNumero('{{ $requisito['chave'] }}', $event.target.value)"
                                class="w-20 rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-white/5 dark:text-white"
                            />
                        </div>
                    @else
                        <label class="-mx-3 flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                            <input
                                type="checkbox"
                                wire:click="toggleRequisitoBool('{{ $requisito['chave'] }}')"
                                @checked($requisito['valor'])
                                class="h-6 w-6 shrink-0 rounded border-gray-300 accent-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-1 dark:border-gray-600 dark:focus:ring-offset-gray-900"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $requisito['label'] }}</span>
                        </label>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
