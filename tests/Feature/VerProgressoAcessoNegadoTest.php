<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\Equipe;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $this->equipeA = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Equipe A']);
    $this->equipeB = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Equipe B']);

    $this->jovemDaEquipeA = Jovem::create([
        'nome' => 'Jovem da Equipe A',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $ramo->id,
        'equipe_id' => $this->equipeA->id,
    ]);
});

it('bloqueia o acesso ao progresso de um jovem de outra equipe', function () {
    $lider = User::factory()->create();
    $lider->equipes()->attach($this->equipeB);
    $this->actingAs($lider);

    // O jovem de outra equipe nem é encontrado na consulta do recurso (404,
    // não 403, pra não revelar que ele existe).
    Livewire::test(VerProgresso::class, ['record' => $this->jovemDaEquipeA->getKey()])
        ->assertNotFound();
});

it('permite o acesso ao progresso de um jovem da propria equipe', function () {
    $lider = User::factory()->create();
    $lider->equipes()->attach($this->equipeA);
    $this->actingAs($lider);

    Livewire::test(VerProgresso::class, ['record' => $this->jovemDaEquipeA->getKey()])
        ->assertOk();
});

it('permite que um admin acesse o progresso de qualquer jovem', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(VerProgresso::class, ['record' => $this->jovemDaEquipeA->getKey()])
        ->assertOk();
});
