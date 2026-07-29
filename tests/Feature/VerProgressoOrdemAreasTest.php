<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lista as areas de desenvolvimento antigas na ordem oficial, nao alfabetica', function () {
    $this->actingAs(User::factory()->create());

    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $ramo->id,
    ]);

    // cria fora de ordem de propósito
    foreach (['Espiritual', 'Afetivo', 'Físico', 'Social', 'Caráter', 'Intelectual'] as $nome) {
        AreaDesenvolvimentoAntiga::create(['ramo_id' => $ramo->id, 'nome' => $nome]);
    }

    $ordemEsperada = ['Físico', 'Intelectual', 'Caráter', 'Afetivo', 'Social', 'Espiritual'];

    $componente = Livewire::test(VerProgresso::class, ['record' => $jovem->getKey()]);

    $ordemObtida = $componente->instance()->getAreasAntigas()->pluck('nome')->all();

    expect($ordemObtida)->toBe($ordemEsperada);
});
