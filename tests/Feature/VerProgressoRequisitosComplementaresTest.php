<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);
});

it('alterna um requisito complementar booleano direto na tela de progresso', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleRequisitoBool', 'senior_antigo_cordao_dourado');

    expect($this->jovem->fresh()->requisitoBool('senior_antigo_cordao_dourado'))->toBeTrue();

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleRequisitoBool', 'senior_antigo_cordao_dourado');

    expect($this->jovem->fresh()->requisitoBool('senior_antigo_cordao_dourado'))->toBeFalse();
});

it('atualiza um requisito complementar do tipo contador direto na tela de progresso', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('atualizarRequisitoNumero', 'senior_antigo_noites_acampadas', 7);

    expect($this->jovem->fresh()->requisitoNumero('senior_antigo_noites_acampadas'))->toBe(7);
});

it('nao cria linhas duplicadas ao atualizar o mesmo requisito complementar mais de uma vez', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('atualizarRequisitoNumero', 'senior_antigo_noites_acampadas', 3)
        ->call('atualizarRequisitoNumero', 'senior_antigo_noites_acampadas', 5);

    expect($this->jovem->requisitosComplementares()->where('chave', 'senior_antigo_noites_acampadas')->count())->toBe(1)
        ->and($this->jovem->fresh()->requisitoNumero('senior_antigo_noites_acampadas'))->toBe(5);
});
