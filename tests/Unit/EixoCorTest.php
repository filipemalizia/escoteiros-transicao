<?php

use App\Models\EixoNovo;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('retorna a cor oficial de cada eixo conhecido', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);

    expect(EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente'])->cor())->toBe('#4db05b')
        ->and(EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Paz e Desenvolvimento'])->cor())->toBe('#194383')
        ->and(EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Habilidades para a Vida'])->cor())->toBe('#e73458')
        ->and(EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Saúde e Bem-estar'])->cor())->toBe('#e2947b');
});

it('retorna uma cor neutra pra um eixo sem cor mapeada', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Inventado']);

    expect($eixo->cor())->toBe('#6b7280');
});
