<?php

use App\Filament\Resources\Jovens\JovemResource;
use App\Filament\Resources\Jovens\Pages\ListJovens;
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

    $this->jovemDaEquipeB = Jovem::create([
        'nome' => 'Jovem da Equipe B',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $ramo->id,
        'equipe_id' => $this->equipeB->id,
    ]);
});

it('mostra apenas os jovens da propria equipe para um usuario comum', function () {
    $lider = User::factory()->create();
    $lider->equipes()->attach($this->equipeA);
    $this->actingAs($lider);

    Livewire::test(ListJovens::class)
        ->assertCanSeeTableRecords([$this->jovemDaEquipeA])
        ->assertCanNotSeeTableRecords([$this->jovemDaEquipeB]);
});

it('mostra todos os jovens para um admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(ListJovens::class)
        ->assertCanSeeTableRecords([$this->jovemDaEquipeA, $this->jovemDaEquipeB]);
});

it('exclui jovens de outras equipes diretamente na query do resource', function () {
    $lider = User::factory()->create();
    $lider->equipes()->attach($this->equipeA);
    $this->actingAs($lider);

    $ids = JovemResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($this->jovemDaEquipeA->id)
        ->and($ids)->not->toContain($this->jovemDaEquipeB->id);
});
