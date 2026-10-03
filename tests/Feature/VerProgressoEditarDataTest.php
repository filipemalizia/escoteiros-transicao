<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $this->itemAntigo = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item antigo']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Teste']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $this->itemNovo = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);

    $this->itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $bloco->id, 'descricao' => 'Desafio especial']);
    $this->itemPersonalizado->jovens()->attach($this->jovem->id);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'atividades_temas']);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $this->itemEspecialidade = $grupo->itens()->create(['texto' => 'Montar barraca']);
});

it('edita a data de conclusao de um item do programa antigo', function () {
    $progresso = ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $this->itemAntigo->id,
        'concluido' => true,
        'data_conclusao' => '2026-01-10',
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirEdicaoData', 'antigo', $this->itemAntigo->id, '2026-01-10')
        ->assertSet('editandoDataValor', '2026-01-10')
        ->set('editandoDataValor', '2026-02-15')
        ->call('salvarEdicaoData')
        ->assertSet('editandoDataTipo', null);

    expect($progresso->fresh()->data_conclusao->toDateString())->toBe('2026-02-15');
});

it('edita a data de conclusao de um item novo', function () {
    $progresso = ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => true,
        'data_conclusao' => '2026-01-10',
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirEdicaoData', 'novo', $this->itemNovo->id, '2026-01-10')
        ->set('editandoDataValor', '2026-02-15')
        ->call('salvarEdicaoData');

    expect($progresso->fresh()->data_conclusao->toDateString())->toBe('2026-02-15');
});

it('edita a data de conclusao de um item personalizado', function () {
    $progresso = ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $this->itemPersonalizado->id,
        'concluido' => true,
        'data_conclusao' => '2026-01-10',
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirEdicaoData', 'personalizado', $this->itemPersonalizado->id, '2026-01-10')
        ->set('editandoDataValor', '2026-02-15')
        ->call('salvarEdicaoData');

    expect($progresso->fresh()->data_conclusao->toDateString())->toBe('2026-02-15');
});

it('edita a data de conclusao de um item de especialidade', function () {
    $progresso = ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->itemEspecialidade->id,
        'concluido' => true,
        'data_conclusao' => '2026-01-10',
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirEdicaoData', 'especialidade', $this->itemEspecialidade->id, '2026-01-10')
        ->set('editandoDataValor', '2026-02-15')
        ->call('salvarEdicaoData');

    expect($progresso->fresh()->data_conclusao->toDateString())->toBe('2026-02-15');
});

it('nao permite editar a data de um item que ainda nao foi concluido', function () {
    $progresso = ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => false,
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirEdicaoData', 'novo', $this->itemNovo->id, null)
        ->set('editandoDataValor', '2026-02-15')
        ->call('salvarEdicaoData');

    expect($progresso->fresh()->data_conclusao)->toBeNull();
});

it('fecha o modal de edicao sem salvar ao cancelar', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => true,
        'data_conclusao' => '2026-01-10',
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirEdicaoData', 'novo', $this->itemNovo->id, '2026-01-10')
        ->call('fecharEdicaoData')
        ->assertSet('editandoDataTipo', null)
        ->assertSet('editandoDataItemId', null);
});
