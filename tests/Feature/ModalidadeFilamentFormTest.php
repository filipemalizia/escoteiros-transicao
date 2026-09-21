<?php

use App\Filament\Resources\Equipes\Pages\CreateEquipe;
use App\Filament\Resources\EspecialidadeDistintivos\Pages\CreateEspecialidadeDistintivo;
use App\Models\Equipe;
use App\Models\EspecialidadeDistintivo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('admin cria uma equipe com modalidade pelo Filament', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);

    Livewire::test(CreateEquipe::class)
        ->fillForm([
            'ramo_id' => $ramo->id,
            'nome' => 'Tropa do Mar',
            'modalidade' => 'Mar',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $equipe = Equipe::where('nome', 'Tropa do Mar')->first();

    expect($equipe->modalidade)->toBe('Mar');
});

it('o campo modalidade da especialidade so aparece quando o tipo e Insignia', function () {
    Livewire::test(CreateEspecialidadeDistintivo::class)
        ->fillForm(['tipo' => 'Especialidade'])
        ->assertFormFieldIsHidden('modalidade')
        ->fillForm(['tipo' => 'Insígnia'])
        ->assertFormFieldIsVisible('modalidade');
});

it('admin marca uma insignia como especifica da modalidade Mar', function () {
    Livewire::test(CreateEspecialidadeDistintivo::class)
        ->fillForm([
            'nome' => 'Grumete',
            'tipo' => 'Insígnia',
            'estrutura' => 'atividades_temas',
            'modalidade' => 'Mar',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $insignia = EspecialidadeDistintivo::where('nome', 'Grumete')->first();

    expect($insignia->modalidade)->toBe('Mar');
});
