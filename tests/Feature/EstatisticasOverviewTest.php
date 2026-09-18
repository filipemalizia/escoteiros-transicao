<?php

use App\Filament\Widgets\EstatisticasOverview;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\Equipe;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('mostra os ramos na ordem oficial, nao alfabetica', function () {
    Ramo::create(['nome' => 'Sênior']);
    Ramo::create(['nome' => 'Pioneiro']);
    Ramo::create(['nome' => 'Lobinho']);
    Ramo::create(['nome' => 'Escoteiro']);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(EstatisticasOverview::class)
        ->assertSeeInOrder(['Jovens - Lobinho', 'Jovens - Escoteiro', 'Jovens - Sênior', 'Jovens - Pioneiro']);
});

it('mostra apenas os itens aguardando avaliacao das equipes do usuario comum', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $equipeA = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Equipe A']);
    $equipeB = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Equipe B']);

    $jovemA = Jovem::create(['nome' => 'Jovem A', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id, 'equipe_id' => $equipeA->id]);
    $jovemB = Jovem::create(['nome' => 'Jovem B', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id, 'equipe_id' => $equipeB->id]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco']);
    $itemA = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'A-1', 'descricao' => 'Item A', 'tipo_acao' => 'Obrigatória']);
    $itemB = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-1', 'descricao' => 'Item B', 'tipo_acao' => 'Obrigatória']);

    ProgressoNovo::create(['jovem_id' => $jovemA->id, 'item_novo_id' => $itemA->id, 'concluido' => false, 'solicitado_pelo_jovem' => true, 'solicitado_em' => now()]);
    ProgressoNovo::create(['jovem_id' => $jovemB->id, 'item_novo_id' => $itemB->id, 'concluido' => false, 'solicitado_pelo_jovem' => true, 'solicitado_em' => now()]);

    $lider = User::factory()->create();
    $lider->equipes()->attach($equipeA);

    $this->actingAs($lider);

    Livewire::test(EstatisticasOverview::class)
        ->assertSeeText('Itens Aguardando Avaliação')
        ->assertSeeText('Nas suas equipes');

    // só o item da equipe A (a que o líder tem acesso) deve contar.
    $stats = (new ReflectionMethod(EstatisticasOverview::class, 'getStats'))
        ->invoke(new EstatisticasOverview);

    $statAvaliacoes = collect($stats)->first(fn ($stat) => str_contains(
        (fn () => $this->label)->call($stat),
        'Aguardando Avaliação'
    ));

    expect((fn () => $this->value)->call($statAvaliacoes))->toBe(1);
});

it('admin ve os itens aguardando avaliacao de todas as equipes', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $equipeA = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Equipe A']);
    $equipeB = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Equipe B']);

    $jovemA = Jovem::create(['nome' => 'Jovem A', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id, 'equipe_id' => $equipeA->id]);
    $jovemB = Jovem::create(['nome' => 'Jovem B', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id, 'equipe_id' => $equipeB->id]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco']);
    $itemA = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'A-1', 'descricao' => 'Item A', 'tipo_acao' => 'Obrigatória']);
    $itemB = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-1', 'descricao' => 'Item B', 'tipo_acao' => 'Obrigatória']);

    ProgressoNovo::create(['jovem_id' => $jovemA->id, 'item_novo_id' => $itemA->id, 'concluido' => false, 'solicitado_pelo_jovem' => true, 'solicitado_em' => now()]);
    ProgressoNovo::create(['jovem_id' => $jovemB->id, 'item_novo_id' => $itemB->id, 'concluido' => false, 'solicitado_pelo_jovem' => true, 'solicitado_em' => now()]);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(EstatisticasOverview::class)->assertSeeText('Em todas as equipes');

    $stats = (new ReflectionMethod(EstatisticasOverview::class, 'getStats'))
        ->invoke(new EstatisticasOverview);

    $statAvaliacoes = collect($stats)->first(fn ($stat) => str_contains(
        (fn () => $this->label)->call($stat),
        'Aguardando Avaliação'
    ));

    expect((fn () => $this->value)->call($statAvaliacoes))->toBe(2);
});

it('conta solicitacoes de especialidade no total de itens aguardando avaliacao', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create(['nome' => 'Jovem A', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $item = $grupo->itens()->create(['texto' => 'Montar barraca']);

    ProgressoEspecialidade::create([
        'jovem_id' => $jovem->id,
        'especialidade_distintivo_item_id' => $item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $stats = (new ReflectionMethod(EstatisticasOverview::class, 'getStats'))
        ->invoke(new EstatisticasOverview);

    $statAvaliacoes = collect($stats)->first(fn ($stat) => str_contains(
        (fn () => $this->label)->call($stat),
        'Aguardando Avaliação'
    ));

    expect((fn () => $this->value)->call($statAvaliacoes))->toBe(1);
});
