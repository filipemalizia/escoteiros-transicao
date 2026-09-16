<?php

use App\Livewire\Portal\Progresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item antigo de teste']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item novo de teste', 'tipo_acao' => 'Obrigatória']);
});

it('mostra os dados do jovem certo depois de autenticado', function () {
    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Progresso::class)
        ->assertSee('Jovem de Teste')
        ->assertSee('Item novo de teste')
        ->assertSee($this->ramo->nome);
});

it('nao mostra o programa antigo no portal', function () {
    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Progresso::class)
        ->assertDontSee('Item antigo de teste')
        ->assertDontSee('Programa Antigo');
});

it('nao expoe metodos de mutacao do checklist do adulto nem de solicitacao do programa antigo', function () {
    expect(method_exists(Progresso::class, 'toggleAntigo'))->toBeFalse()
        ->and(method_exists(Progresso::class, 'toggleNovo'))->toBeFalse()
        ->and(method_exists(Progresso::class, 'solicitarAntigo'))->toBeFalse();
});

it('encerra a sessao do portal ao clicar em sair', function () {
    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Progresso::class)
        ->call('sair')
        ->assertRedirect(route('portal.login.mostrar'));

    expect(session()->has('portal_jovem_id'))->toBeFalse();
});
