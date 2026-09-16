<?php

use App\Models\Equipe;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->equipeA = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe A']);
    $this->equipeB = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe B']);

    $this->jovemDaEquipeA = Jovem::create([
        'nome' => 'Jovem da Equipe A',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
        'equipe_id' => $this->equipeA->id,
    ]);

    $this->jovemSemEquipe = Jovem::create([
        'nome' => 'Jovem sem Equipe',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);
});

it('permite que um admin veja e edite qualquer jovem', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    expect($admin->can('view', $this->jovemDaEquipeA))->toBeTrue()
        ->and($admin->can('update', $this->jovemDaEquipeA))->toBeTrue()
        ->and($admin->can('view', $this->jovemSemEquipe))->toBeTrue();
});

it('permite que um usuario da equipe veja e edite o jovem da propria equipe', function () {
    $lider = User::factory()->create();
    $lider->equipes()->attach($this->equipeA);

    expect($lider->can('view', $this->jovemDaEquipeA))->toBeTrue()
        ->and($lider->can('update', $this->jovemDaEquipeA))->toBeTrue();
});

it('nega acesso a um jovem de outra equipe', function () {
    $lider = User::factory()->create();
    $lider->equipes()->attach($this->equipeB);

    expect($lider->can('view', $this->jovemDaEquipeA))->toBeFalse()
        ->and($lider->can('update', $this->jovemDaEquipeA))->toBeFalse();
});

it('nega acesso de um usuario comum a um jovem sem equipe atribuida', function () {
    $lider = User::factory()->create();
    $lider->equipes()->attach($this->equipeA);

    expect($lider->can('view', $this->jovemSemEquipe))->toBeFalse();
});
