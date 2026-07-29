<?php

use App\Filament\Resources\BlocoNovos\Pages\EditBlocoNovo;
use App\Filament\Resources\CompetenciaAntigas\Pages\EditCompetenciaAntiga;
use App\Filament\Resources\CompetenciaAntigas\Pages\ListCompetenciaAntigas;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EquivalenciaBloco;
use App\Models\ItemAntigo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);
});

it('bloqueia a exclusao de uma competencia com item que tem progresso concluido', function () {
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $item = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item 1']);

    ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    Livewire::test(EditCompetenciaAntiga::class, ['record' => $competencia->getKey()])
        ->callAction('delete')
        ->assertNotified('Não é possível excluir esta competência');

    expect(CompetenciaAntiga::find($competencia->id))->not->toBeNull();
});

it('permite excluir uma competencia sem dados vinculados', function () {
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-002', 'descricao' => 'Item 2']);

    Livewire::test(EditCompetenciaAntiga::class, ['record' => $competencia->getKey()])
        ->callAction('delete');

    expect(CompetenciaAntiga::find($competencia->id))->toBeNull();
});

it('bloqueia a exclusao de um bloco com equivalencia de bloco cadastrada', function () {
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $itemAntigo = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-003', 'descricao' => 'Item 3']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco']);

    EquivalenciaBloco::create(['item_antigo_id' => $itemAntigo->id, 'bloco_novo_id' => $bloco->id]);

    Livewire::test(EditBlocoNovo::class, ['record' => $bloco->getKey()])
        ->callAction('delete')
        ->assertNotified('Não é possível excluir este bloco');

    expect(BlocoNovo::find($bloco->id))->not->toBeNull();
});

it('bloqueia a exclusao em lote de competencias quando alguma tem dados vinculados', function () {
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);

    $competenciaSegura = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    ItemAntigo::create(['competencia_id' => $competenciaSegura->id, 'codigo' => 'FIS-004', 'descricao' => 'Item 4']);

    $competenciaProtegida = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Higiene']);
    $itemProtegido = ItemAntigo::create(['competencia_id' => $competenciaProtegida->id, 'codigo' => 'FIS-005', 'descricao' => 'Item 5']);

    ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $itemProtegido->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    Livewire::test(ListCompetenciaAntigas::class)
        ->callTableBulkAction('delete', [$competenciaSegura, $competenciaProtegida])
        ->assertNotified('Não é possível excluir uma ou mais competências selecionadas');

    expect(CompetenciaAntiga::find($competenciaSegura->id))->not->toBeNull()
        ->and(CompetenciaAntiga::find($competenciaProtegida->id))->not->toBeNull();
});
