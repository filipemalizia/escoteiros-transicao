@props(['tipo', 'titulo', 'imagemUrl' => null, 'jovem', 'nivel' => null])

{{--
    Dispara o modal global `x-progresso.modal-compartilhar` (deve existir em
    algum lugar da página) via evento de `window` — assim este botão pode
    ficar dentro de qualquer estrutura (inclusive dentro de outro elemento
    clicável, como o cabeçalho de um accordion) sem precisar saber nada
    sobre onde o modal está, nem depender de round-trip com o servidor.

    Recebe o `$jovem` (model) em vez de nome/ramo já prontos, pra nunca um
    dos vários lugares que usam este botão esquecer de mandar o ramo (usado
    no texto de etapa/reconhecimento, "X do Ramo Y"). `nivel` só se aplica a
    especialidade/insígnia de estrutura `itens_niveis` (1 ou 2) — deixa null
    pra qualquer outro tipo, ou quando a estrutura não tem níveis.
--}}
<button
    type="button"
    x-data
    x-on:click.stop.prevent="window.dispatchEvent(new CustomEvent('abrir-cartao-conquista', { detail: {{ Illuminate\Support\Js::from([
        'tipo' => $tipo,
        'titulo' => $titulo,
        'imagemUrl' => $imagemUrl,
        'jovemNome' => $jovem->nomeExibicao(),
        'ramoNome' => $jovem->ramoAtual->nome,
        'nivel' => $nivel,
    ]) }} }))"
    {{ $attributes->class(['inline-flex shrink-0 items-center gap-1 rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-white/5']) }}
>
    <x-filament::icon icon="heroicon-o-share" class="h-3.5 w-3.5" />
    Compartilhar
</button>
