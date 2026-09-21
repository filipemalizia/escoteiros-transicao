<?php

use App\Filament\Resources\Equipes\Pages\EditEquipe;
use App\Filament\Resources\Equipes\RelationManagers\JovensRelationManager;
use App\Models\Equipe;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->outroRamo = Ramo::create(['nome' => 'Pioneiro']);
    $this->equipe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe A']);

    $this->jovemSemEquipe = Jovem::create([
        'nome' => 'Jovem sem equipe',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);
});

it('associa um jovem existente do mesmo ramo a equipe', function () {
    Livewire::test(JovensRelationManager::class, [
        'ownerRecord' => $this->equipe,
        'pageClass' => EditEquipe::class,
    ])
        ->callTableAction('associate', data: ['recordId' => $this->jovemSemEquipe->getKey()]);

    expect($this->jovemSemEquipe->fresh()->equipe_id)->toBe($this->equipe->id);
});

it('associa um jovem de outro ramo, e o ramo dele passa a ser o da equipe', function () {
    $jovemPioneiro = Jovem::create([
        'nome' => 'Jovem Pioneiro',
        'data_nascimento' => '2008-01-01',
        'ramo_atual_id' => $this->outroRamo->id,
    ]);

    Livewire::test(JovensRelationManager::class, [
        'ownerRecord' => $this->equipe,
        'pageClass' => EditEquipe::class,
    ])
        ->callTableAction('associate', data: ['recordId' => $jovemPioneiro->getKey()]);

    $jovemPioneiro->refresh();

    expect($jovemPioneiro->equipe_id)->toBe($this->equipe->id)
        ->and($jovemPioneiro->ramo_atual_id)->toBe($this->ramo->id);
});

it('cria um jovem novo ja vinculado a equipe, herdando o ramo dela', function () {
    Livewire::test(JovensRelationManager::class, [
        'ownerRecord' => $this->equipe,
        'pageClass' => EditEquipe::class,
    ])
        ->callTableAction('create', data: [
            'nome' => 'Jovem Criado Aqui',
            'data_nascimento' => '2012-03-03',
        ]);

    $jovem = Jovem::where('nome', 'Jovem Criado Aqui')->first();

    expect($jovem)->not->toBeNull()
        ->and($jovem->equipe_id)->toBe($this->equipe->id)
        ->and($jovem->ramo_atual_id)->toBe($this->ramo->id);
});

it('desvincula um jovem da equipe', function () {
    $this->jovemSemEquipe->update(['equipe_id' => $this->equipe->id]);

    Livewire::test(JovensRelationManager::class, [
        'ownerRecord' => $this->equipe,
        'pageClass' => EditEquipe::class,
    ])
        ->callTableAction('dissociate', $this->jovemSemEquipe);

    expect($this->jovemSemEquipe->fresh()->equipe_id)->toBeNull();
});
