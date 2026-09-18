<?php

use App\Filament\Resources\EspecialidadeDistintivos\Pages\CreateEspecialidadeDistintivo;
use App\Filament\Resources\EspecialidadeDistintivos\Pages\EditEspecialidadeDistintivo;
use App\Filament\Resources\EspecialidadeDistintivos\RelationManagers\GruposRelationManager;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function criarEixosMeioAmbiente(): array
{
    $lobinho = Ramo::create(['nome' => 'Lobinho']);
    $escoteiro = Ramo::create(['nome' => 'Escoteiro']);

    return [
        EixoNovo::create(['ramo_id' => $lobinho->id, 'nome' => 'Meio Ambiente']),
        EixoNovo::create(['ramo_id' => $escoteiro->id, 'nome' => 'Meio Ambiente']),
    ];
}

it('liga uma especialidade a mais de um eixo (ramo+eixo) ao mesmo tempo', function () {
    [$eixoLobinho, $eixoEscoteiro] = criarEixosMeioAmbiente();

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);
    $especialidade->eixosNovos()->attach([$eixoLobinho->id, $eixoEscoteiro->id]);

    expect($especialidade->eixosNovos)->toHaveCount(2)
        ->and($especialidade->eixosNovos->pluck('ramo.nome')->sort()->values()->all())
        ->toBe(['Escoteiro', 'Lobinho']);

    // inverso: o EixoNovo também enxerga a especialidade
    expect($eixoLobinho->fresh()->especialidadesDistintivos)->toHaveCount(1);
});

it('grupo com quantidade_minima null exige todos os itens; com numero exige so aquele minimo', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Educação Alimentar', 'tipo' => 'Especialidade']);

    $conhecer = $especialidade->grupos()->create(['chave' => 'conhecer', 'quantidade_minima' => null]);
    $fazer = $especialidade->grupos()->create(['chave' => 'fazer', 'quantidade_minima' => 2]);

    expect($conhecer->exigeTodosOsItens())->toBeTrue()
        ->and($fazer->exigeTodosOsItens())->toBeFalse()
        ->and($fazer->quantidade_minima)->toBe(2);
});

it('itens pertencem a um grupo e sao apagados em cascata com ele', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Montar barraca', 'fonte_item_id' => 425]);
    $grupo->itens()->create(['texto' => 'Organizar material', 'fonte_item_id' => 426]);

    expect($grupo->itens)->toHaveCount(2);

    $grupo->delete();

    expect(EspecialidadeDistintivoItem::count())->toBe(0);
});

it('admin cria uma especialidade itens_niveis com imagens e eixos pelo Filament', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    [$eixoLobinho, $eixoEscoteiro] = criarEixosMeioAmbiente();

    Livewire::test(CreateEspecialidadeDistintivo::class)
        ->fillForm([
            'nome' => 'Acampamento',
            'tipo' => 'Especialidade',
            'estrutura' => 'itens_niveis',
            'regra_niveis' => 'Concluir quatro para nível 1 e oito para nível 2',
            'minimo_nivel_1' => 4,
            'minimo_nivel_2' => 8,
            'eixosNovos' => [$eixoLobinho->id, $eixoEscoteiro->id],
            'fonte_specialty_id' => 62,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $especialidade = EspecialidadeDistintivo::where('fonte_specialty_id', 62)->first();

    expect($especialidade)->not->toBeNull()
        ->and($especialidade->estrutura)->toBe('itens_niveis')
        ->and($especialidade->minimo_nivel_1)->toBe(4)
        ->and($especialidade->minimo_nivel_2)->toBe(8)
        ->and($especialidade->eixosNovos)->toHaveCount(2);
});

it('admin cria uma especialidade atividades_temas com sugestao de temas pelo Filament', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(CreateEspecialidadeDistintivo::class)
        ->fillForm([
            'nome' => 'Educação Alimentar e Nutricional',
            'tipo' => 'Especialidade',
            'estrutura' => 'atividades_temas',
            'sugestao_temas' => ['Princípios de alimentação saudável', 'Leitura de rótulos'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $especialidade = EspecialidadeDistintivo::where('nome', 'Educação Alimentar e Nutricional')->first();

    expect($especialidade->estrutura)->toBe('atividades_temas')
        ->and($especialidade->sugestao_temas)->toBe(['Princípios de alimentação saudável', 'Leitura de rótulos'])
        ->and($especialidade->minimo_nivel_1)->toBeNull();
});

it('admin cria um grupo via relation manager', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);

    Livewire::test(GruposRelationManager::class, [
        'ownerRecord' => $especialidade,
        'pageClass' => EditEspecialidadeDistintivo::class,
    ])
        ->callTableAction('create', data: [
            'chave' => 'itens',
            'quantidade_minima' => 4,
            'itens' => [
                ['texto' => 'Montar barraca', 'ordem' => 1],
                ['texto' => 'Organizar material', 'ordem' => 2],
            ],
        ])
        ->assertHasNoTableActionErrors();

    $grupo = $especialidade->grupos()->where('chave', 'itens')->first();

    expect($grupo)->not->toBeNull()
        ->and($grupo->quantidade_minima)->toBe(4)
        ->and($grupo->itens)->toHaveCount(2);
});
