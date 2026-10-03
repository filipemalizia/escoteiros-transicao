<?php

use App\Support\Destaque;

it('envolve a ocorrencia da busca com mark, sem diferenciar maiusculas/minusculas', function () {
    expect(Destaque::html('Acampamento', 'acampa'))
        ->toBe('<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">Acampa</mark>mento');
});

it('retorna o texto so escapado quando a busca esta em branco', function () {
    expect(Destaque::html('Item <script>', ''))->toBe('Item &lt;script&gt;');
});

it('escapa o texto original mesmo quando ha destaque', function () {
    expect(Destaque::html('<b>Acampamento</b>', 'acampa'))
        ->toBe('&lt;b&gt;<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">Acampa</mark>mento&lt;/b&gt;');
});

it('nao da erro quando a busca nao aparece no texto', function () {
    expect(Destaque::html('Primeiros Socorros', 'acampa'))->toBe('Primeiros Socorros');
});

it('destaca cada palavra da busca de forma independente', function () {
    expect(Destaque::html('Acampamento noturno com fogueira', 'acampa fogueira'))->toBe(
        '<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">Acampa</mark>mento noturno com '.
        '<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">fogueira</mark>'
    );
});

it('destaca o trecho acentuado do texto original mesmo buscando sem acento', function () {
    expect(Destaque::html('Férias de Verão', 'ferias verao'))->toBe(
        '<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">Férias</mark> de '.
        '<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">Verão</mark>'
    );
});

it('destaca o trecho sem acento do texto original mesmo buscando com acento', function () {
    expect(Destaque::html('Ferias de Verao', 'férias verão'))->toBe(
        '<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">Ferias</mark> de '.
        '<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">Verao</mark>'
    );
});
