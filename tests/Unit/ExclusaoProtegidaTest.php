<?php

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\Equivalencia;
use App\Models\EquivalenciaBloco;
use App\Models\EquivalenciaEspecialidade;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $this->area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $this->competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $this->area->id, 'descricao' => 'Saúde']);
    $this->itemAntigo = ItemAntigo::create(['competencia_id' => $this->competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item 1']);

    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco']);
    $this->itemNovo = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'HPV-001', 'descricao' => 'Item 1', 'tipo_acao' => 'Obrigatória']);
});

it('ItemAntigo sem dados vinculados pode ser considerado seguro pra exclusao', function () {
    expect($this->itemAntigo->possuiDadosVinculados())->toBeFalse();
});

it('ItemAntigo com progresso concluido possui dados vinculados', function () {
    ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $this->itemAntigo->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    expect($this->itemAntigo->possuiDadosVinculados())->toBeTrue();
});

it('ItemAntigo com progresso nao concluido nao possui dados vinculados', function () {
    ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $this->itemAntigo->id,
        'concluido' => false,
    ]);

    expect($this->itemAntigo->possuiDadosVinculados())->toBeFalse();
});

it('ItemAntigo com equivalencia cadastrada possui dados vinculados', function () {
    Equivalencia::create([
        'item_antigo_id' => $this->itemAntigo->id,
        'item_novo_id' => $this->itemNovo->id,
        'tipo_equivalencia' => '1-1',
    ]);

    expect($this->itemAntigo->possuiDadosVinculados())->toBeTrue();
});

it('ItemAntigo com equivalencia de bloco cadastrada possui dados vinculados', function () {
    EquivalenciaBloco::create([
        'item_antigo_id' => $this->itemAntigo->id,
        'bloco_novo_id' => $this->bloco->id,
    ]);

    expect($this->itemAntigo->possuiDadosVinculados())->toBeTrue();
});

it('ItemNovo com progresso concluido possui dados vinculados', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    expect($this->itemNovo->possuiDadosVinculados())->toBeTrue();
});

it('ItemNovo com equivalencia de especialidade cadastrada possui dados vinculados', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);

    EquivalenciaEspecialidade::create([
        'especialidade_distintivo_id' => $especialidade->id,
        'item_novo_id' => $this->itemNovo->id,
    ]);

    expect($this->itemNovo->possuiDadosVinculados())->toBeTrue();
});

it('CompetenciaAntiga reflete se algum de seus itens possui dados vinculados', function () {
    expect($this->competencia->possuiItensComDadosVinculados())->toBeFalse();

    ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $this->itemAntigo->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    expect($this->competencia->fresh()->possuiItensComDadosVinculados())->toBeTrue();
});

it('BlocoNovo reflete se possui equivalencia de bloco vinculada, mesmo sem progresso nos itens', function () {
    expect($this->bloco->possuiItensComDadosVinculados())->toBeFalse();

    EquivalenciaBloco::create([
        'item_antigo_id' => $this->itemAntigo->id,
        'bloco_novo_id' => $this->bloco->id,
    ]);

    expect($this->bloco->fresh()->possuiItensComDadosVinculados())->toBeTrue();
});

it('EspecialidadeDistintivo reflete se algum de seus itens possui dados vinculados', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);
    $item = ItemNovo::create([
        'bloco_id' => $this->bloco->id,
        'especialidade_id' => $especialidade->id,
        'codigo' => 'ACP-001',
        'descricao' => 'Item 1',
        'tipo_acao' => 'Obrigatória',
    ]);

    expect($especialidade->possuiItensComDadosVinculados())->toBeFalse();

    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    expect($especialidade->fresh()->possuiItensComDadosVinculados())->toBeTrue();
});

it('EspecialidadeDistintivo reflete se possui equivalencia de especialidade vinculada, mesmo sem progresso nos itens', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);

    expect($especialidade->possuiItensComDadosVinculados())->toBeFalse();

    EquivalenciaEspecialidade::create([
        'especialidade_distintivo_id' => $especialidade->id,
        'item_novo_id' => $this->itemNovo->id,
    ]);

    expect($especialidade->fresh()->possuiItensComDadosVinculados())->toBeTrue();
});
