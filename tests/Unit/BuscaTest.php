<?php

use App\Support\Busca;

it('quebra a busca em palavras, ignorando espacos extras', function () {
    expect(Busca::palavras('  acampamento   noturno  '))->toBe(['acampamento', 'noturno']);
});

it('retorna array vazio pra busca em branco', function () {
    expect(Busca::palavras(''))->toBe([])
        ->and(Busca::palavras('   '))->toBe([]);
});

it('confere se todas as palavras aparecem em algum lugar do conjunto de textos, sem exigir a mesma ordem ou o mesmo campo', function () {
    expect(Busca::contemTodasAsPalavras(['Acampamento', 'Atividade noturna com fogueira'], 'noturna acampa'))->toBeTrue()
        ->and(Busca::contemTodasAsPalavras(['Acampamento', 'Primeiros socorros'], 'noturna acampa'))->toBeFalse();
});

it('ignora maiusculas/minusculas ao conferir as palavras', function () {
    expect(Busca::contemTodasAsPalavras('ACAMPAMENTO NOTURNO', 'acampamento noturno'))->toBeTrue();
});

it('busca em branco sempre bate (nao filtra nada)', function () {
    expect(Busca::contemTodasAsPalavras('qualquer coisa', ''))->toBeTrue();
});

it('aceita um unico texto (nao precisa ser array)', function () {
    expect(Busca::contemTodasAsPalavras('Acampamento noturno', 'acampa noturno'))->toBeTrue();
});

it('ignora acentos: busca sem acento encontra texto acentuado e vice-versa', function () {
    expect(Busca::contemTodasAsPalavras('Férias de Verão', 'ferias verao'))->toBeTrue()
        ->and(Busca::contemTodasAsPalavras('Ferias de Verao', 'férias verão'))->toBeTrue()
        ->and(Busca::contemTodasAsPalavras('Educação Física', 'educacao fisica'))->toBeTrue();
});

it('normaliza removendo acento e caixa', function () {
    expect(Busca::normalizar('FÉRIAS de Verão'))->toBe('ferias de verao');
});
