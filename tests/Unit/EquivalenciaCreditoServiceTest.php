<?php

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\Equivalencia;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\EquivalenciaCreditoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new EquivalenciaCreditoService;

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'FÍSICO']);
    $this->competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Autoconhecimento']);
});

function criarItemAntigo(CompetenciaAntiga $competencia, string $codigo): ItemAntigo
{
    return ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => $codigo, 'descricao' => $codigo]);
}

function criarItemNovo(BlocoNovo $bloco, string $codigo, string $tipo = 'Obrigatória'): ItemNovo
{
    return ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => $codigo, 'descricao' => $codigo, 'tipo_acao' => $tipo]);
}

function marcarAntigo(Jovem $jovem, ItemAntigo $item): void
{
    ProgressoAntigo::create([
        'jovem_id' => $jovem->id,
        'item_antigo_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);
}

function marcarNovo(Jovem $jovem, ItemNovo $item): void
{
    ProgressoNovo::create([
        'jovem_id' => $jovem->id,
        'item_novo_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);
}

it('1-1: antigo concluido credita o novo correspondente, e vice-versa', function () {
    $antigo = criarItemAntigo($this->competencia, 'A-001');
    $novo = criarItemNovo($this->bloco, 'N-001');

    Equivalencia::create(['item_antigo_id' => $antigo->id, 'item_novo_id' => $novo->id, 'tipo_equivalencia' => '1-1']);

    expect($this->service->itemNovoConcluido($this->jovem, $novo))->toBeFalse();

    marcarAntigo($this->jovem, $antigo);

    expect($this->service->itemNovoConcluido($this->jovem, $novo))->toBeTrue();

    // vice-versa: outro par, creditando o antigo a partir do novo marcado direto
    $antigo2 = criarItemAntigo($this->competencia, 'A-002');
    $novo2 = criarItemNovo($this->bloco, 'N-002');
    Equivalencia::create(['item_antigo_id' => $antigo2->id, 'item_novo_id' => $novo2->id, 'tipo_equivalencia' => '1-1']);

    expect($this->service->itemAntigoConcluido($this->jovem, $antigo2))->toBeFalse();

    marcarNovo($this->jovem, $novo2);

    expect($this->service->itemAntigoConcluido($this->jovem, $antigo2))->toBeTrue();
});

it('N-1: precisa de TODOS os antigos mapeados pro mesmo novo para creditar o novo', function () {
    $antigo1 = criarItemAntigo($this->competencia, 'A-010');
    $antigo2 = criarItemAntigo($this->competencia, 'A-011');
    $novo = criarItemNovo($this->bloco, 'N-010');

    Equivalencia::create(['item_antigo_id' => $antigo1->id, 'item_novo_id' => $novo->id, 'tipo_equivalencia' => 'N-1']);
    Equivalencia::create(['item_antigo_id' => $antigo2->id, 'item_novo_id' => $novo->id, 'tipo_equivalencia' => 'N-1']);

    marcarAntigo($this->jovem, $antigo1);

    expect($this->service->itemNovoConcluido($this->jovem, $novo))->toBeFalse();

    marcarAntigo($this->jovem, $antigo2);

    expect($this->service->itemNovoConcluido($this->jovem, $novo))->toBeTrue();
});

it('1-N: um antigo credita cada novo individualmente, mas so credita de volta quando AMBOS os novos estao concluidos', function () {
    $antigo = criarItemAntigo($this->competencia, 'A-020');
    $novo1 = criarItemNovo($this->bloco, 'N-020');
    $novo2 = criarItemNovo($this->bloco, 'N-021');

    Equivalencia::create(['item_antigo_id' => $antigo->id, 'item_novo_id' => $novo1->id, 'tipo_equivalencia' => '1-N']);
    Equivalencia::create(['item_antigo_id' => $antigo->id, 'item_novo_id' => $novo2->id, 'tipo_equivalencia' => '1-N']);

    marcarAntigo($this->jovem, $antigo);

    // cada novo, individualmente, ja conta como concluido so com o antigo feito
    expect($this->service->itemNovoConcluido($this->jovem, $novo1))->toBeTrue()
        ->and($this->service->itemNovoConcluido($this->jovem, $novo2))->toBeTrue();

    // mas outro antigo (sem marcacao direta) só é creditado de volta quando AMBOS os novos leigos estiverem concluidos
    $antigoSemMarcacao = criarItemAntigo($this->competencia, 'A-021');
    Equivalencia::create(['item_antigo_id' => $antigoSemMarcacao->id, 'item_novo_id' => $novo1->id, 'tipo_equivalencia' => '1-N']);
    Equivalencia::create(['item_antigo_id' => $antigoSemMarcacao->id, 'item_novo_id' => $novo2->id, 'tipo_equivalencia' => '1-N']);

    $novoIsolado1 = criarItemNovo($this->bloco, 'N-030');
    $novoIsolado2 = criarItemNovo($this->bloco, 'N-031');
    $antigoIsolado = criarItemAntigo($this->competencia, 'A-030');
    Equivalencia::create(['item_antigo_id' => $antigoIsolado->id, 'item_novo_id' => $novoIsolado1->id, 'tipo_equivalencia' => '1-N']);
    Equivalencia::create(['item_antigo_id' => $antigoIsolado->id, 'item_novo_id' => $novoIsolado2->id, 'tipo_equivalencia' => '1-N']);

    marcarNovo($this->jovem, $novoIsolado1);

    expect($this->service->itemAntigoConcluido($this->jovem, $antigoIsolado))->toBeFalse();

    marcarNovo($this->jovem, $novoIsolado2);

    expect($this->service->itemAntigoConcluido($this->jovem, $antigoIsolado))->toBeTrue();
});

it('sem equivalencia cadastrada: so conta como concluido se marcado diretamente', function () {
    $antigo = criarItemAntigo($this->competencia, 'A-040');
    $novo = criarItemNovo($this->bloco, 'N-040');

    expect($this->service->itemAntigoConcluido($this->jovem, $antigo))->toBeFalse()
        ->and($this->service->itemNovoConcluido($this->jovem, $novo))->toBeFalse();

    marcarAntigo($this->jovem, $antigo);
    marcarNovo($this->jovem, $novo);

    expect($this->service->itemAntigoConcluido($this->jovem, $antigo))->toBeTrue()
        ->and($this->service->itemNovoConcluido($this->jovem, $novo))->toBeTrue();
});

it('protege contra ciclo de equivalencia mal cadastrada, sem travar nem estourar erro', function () {
    $antigo = criarItemAntigo($this->competencia, 'A-050');
    $novo = criarItemNovo($this->bloco, 'N-050');

    // equivalencia circular: antigo <-> novo, nenhum dos dois marcado diretamente
    Equivalencia::create(['item_antigo_id' => $antigo->id, 'item_novo_id' => $novo->id, 'tipo_equivalencia' => '1-1']);

    expect($this->service->itemAntigoConcluido($this->jovem, $antigo))->toBeFalse()
        ->and($this->service->itemNovoConcluido($this->jovem, $novo))->toBeFalse();
});
