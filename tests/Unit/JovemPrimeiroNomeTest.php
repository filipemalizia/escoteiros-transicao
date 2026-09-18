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
