<?php

/**
 * Regressão: misturar um atributo dinâmico (`:icon="$icone"`) com
 * `{{ $attributes->class(["... {$variavel}"]) }}` (interpolação de chaves
 * dentro de uma string) na MESMA tag de componente quebra o parser de
 * atributos do Blade — o resultado sai como texto cru na página
 * (`<x-filament::icon :icon="$icone" ...>`), que o navegador tenta rodar
 * como Alpine/JS e estoura "$icone is not defined" no console. Corrigido
 * separando a variável como item próprio do array em vez de interpolar
 * dentro da string. Esse teste garante que nenhuma sintaxe Blade/PHP crua
 * escape pro HTML renderizado.
 */
it('o componente status-icone nao vaza sintaxe blade/php crua no html', function () {
    $html = (string) $this->blade('<x-progresso.status-icone :concluido="false" :solicitado="false" />');

    expect($html)->toContain('<svg')
        ->not->toContain('$icone')
        ->not->toContain('{$');
});

it('o componente imagem-badge nao vaza sintaxe blade/php crua no html', function () {
    $html = (string) $this->blade('<x-progresso.imagem-badge :url="null" alt="teste" size="h-24 w-24" />');

    expect($html)->toContain('h-24 w-24')
        ->not->toContain('$size')
        ->not->toContain('{$');
});
