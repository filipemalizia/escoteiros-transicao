<?php

use App\Filament\Resources\Jovens\Pages\EditJovem;
use App\Models\Equipe;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->equipeDoChefe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe do Chefe']);
    $this->outraEquipe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Outra Equipe']);

    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
        'equipe_id' => $this->equipeDoChefe->id,
    ]);

    $this->chefe = User::factory()->create(['is_admin' => false]);
    $this->chefe->equipes()->attach($this->equipeDoChefe);
    $this->actingAs($this->chefe);
});

it('permite que o chefe salve o jovem numa equipe que ele gerencia', function () {
    Livewire::test(EditJovem::class, ['record' => $this->jovem->getKey()])
        ->fillForm([
            'ramo_atual_id' => $this->ramo->id,
            'equipe_id' => $this->equipeDoChefe->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->jovem->fresh()->equipe_id)->toBe($this->equipeDoChefe->id);
});

it('bloqueia, mesmo manipulando o formulario direto, mover o jovem pra uma equipe que o chefe nao gerencia', function () {
    Livewire::test(EditJovem::class, ['record' => $this->jovem->getKey()])
        ->fillForm([
            'ramo_atual_id' => $this->ramo->id,
            'equipe_id' => $this->outraEquipe->id,
        ])
        ->call('save')
        ->assertHasFormErrors(['equipe_id']);

    expect($this->jovem->fresh()->equipe_id)->toBe($this->equipeDoChefe->id);
});

it('admin pode mover o jovem pra qualquer equipe', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(EditJovem::class, ['record' => $this->jovem->getKey()])
        ->fillForm([
            'ramo_atual_id' => $this->ramo->id,
            'equipe_id' => $this->outraEquipe->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->jovem->fresh()->equipe_id)->toBe($this->outraEquipe->id);
});
