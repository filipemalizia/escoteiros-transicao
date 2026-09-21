<?php

use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('extrai o primeiro nome de um nome completo', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Maria Clara Souza', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);

    expect($jovem->primeiroNome())->toBe('Maria');
});

it('mantem o nome inteiro quando o jovem so tem um nome cadastrado', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Maria', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);

    expect($jovem->primeiroNome())->toBe('Maria');
});

it('usa o nome do meio no nome de exibicao quando o primeiro nome e comum/curto demais', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);

    $jovem = Jovem::create(['nome' => 'Ana Sophia Ferreira Lima', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    expect($jovem->nomeExibicao())->toBe('Ana Sophia Lima');

    $jovem = Jovem::create(['nome' => 'Maria Eduarda Costa', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    expect($jovem->nomeExibicao())->toBe('Maria Eduarda Costa');
});

it('usa so primeiro nome e sobrenome no nome de exibicao quando o primeiro nome ja identifica bem', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Bernardo Henrique Oliveira', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);

    expect($jovem->nomeExibicao())->toBe('Bernardo Oliveira');
});

it('mantem o nome inteiro no nome de exibicao quando tem so 2 nomes cadastrados', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Bernardo Oliveira', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);

    expect($jovem->nomeExibicao())->toBe('Bernardo Oliveira');
});
