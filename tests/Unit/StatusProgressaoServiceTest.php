<?php

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new StatusProgressaoService;
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);
});

function marcarConcluido(Jovem $jovem, string $tipo, int $itemId): void
{
    $modelo = $tipo === 'antigo' ? ProgressoAntigo::class : ProgressoNovo::class;
    $coluna = $tipo === 'antigo' ? 'item_antigo_id' : 'item_novo_id';

    $modelo::create([
        'jovem_id' => $jovem->id,
        $coluna => $itemId,
        'concluido' => true,
        'data_conclusao' => today(),
        'registrado_por_id' => null,
    ]);
}

it('marca competencia como Concluído quando 100% dos itens estao concluidos, e Parcial quando nao', function () {
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'FÍSICO']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $item1 = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item 1']);
    $item2 = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-002', 'descricao' => 'Item 2']);

    $status = $this->service->statusCompetencia($this->jovem, $competencia->fresh());
    expect($status['status'])->toBe('Pendente');

    marcarConcluido($this->jovem, 'antigo', $item1->id);
    $status = $this->service->statusCompetencia($this->jovem, $competencia->fresh());
    expect($status['status'])->toBe('Parcial')
        ->and($status['itens_concluidos'])->toBe(1)
        ->and($status['itens_necessarios'])->toBe(2);

    marcarConcluido($this->jovem, 'antigo', $item2->id);
    $status = $this->service->statusCompetencia($this->jovem, $competencia->fresh());
    expect($status['status'])->toBe('Concluído');
});

it('marca bloco como Concluído quando obrigatorias ok e variaveis suficientes', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Autoconhecimento', 'quantidade_minima_variaveis' => 2]);

    $obrigatoria = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
    $variavel1 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-002', 'descricao' => 'Var 1', 'tipo_acao' => 'Variável']);
    $variavel2 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-003', 'descricao' => 'Var 2', 'tipo_acao' => 'Variável']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-004', 'descricao' => 'Var 3', 'tipo_acao' => 'Variável']);

    marcarConcluido($this->jovem, 'novo', $obrigatoria->id);
    marcarConcluido($this->jovem, 'novo', $variavel1->id);

    $status = $this->service->statusBloco($this->jovem, $bloco->fresh());
    expect($status['status'])->toBe('Parcial'); // só 1 de 2 variáveis necessárias

    marcarConcluido($this->jovem, 'novo', $variavel2->id);

    $status = $this->service->statusBloco($this->jovem, $bloco->fresh());
    expect($status['status'])->toBe('Concluído')
        ->and($status['variaveis_concluidas'])->toBe(2)
        ->and($status['variaveis_necessarias'])->toBe(2);
});

it('marca bloco como Concluído via Substitutiva mesmo com poucas variaveis', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Autoconhecimento', 'quantidade_minima_variaveis' => 3]);

    $obrigatoria = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-002', 'descricao' => 'Var 1', 'tipo_acao' => 'Variável']);
    $substitutiva = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-003', 'descricao' => 'Sub', 'tipo_acao' => 'Substitutiva']);

    marcarConcluido($this->jovem, 'novo', $obrigatoria->id);
    // nenhuma variável concluída ainda

    $status = $this->service->statusBloco($this->jovem, $bloco->fresh());
    expect($status['status'])->toBe('Parcial');

    marcarConcluido($this->jovem, 'novo', $substitutiva->id);

    $status = $this->service->statusBloco($this->jovem, $bloco->fresh());
    expect($status['status'])->toBe('Concluído')
        ->and($status['substitutiva_concluida'])->toBeTrue()
        ->and($status['variaveis_concluidas'])->toBe(0);
});

it('nunca marca bloco como Concluído se falta obrigatoria, mesmo com variaveis e substitutiva ok', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Autoconhecimento', 'quantidade_minima_variaveis' => 1]);

    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-001', 'descricao' => 'Obg pendente', 'tipo_acao' => 'Obrigatória']);
    $variavel = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-002', 'descricao' => 'Var 1', 'tipo_acao' => 'Variável']);
    $substitutiva = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-003', 'descricao' => 'Sub', 'tipo_acao' => 'Substitutiva']);

    marcarConcluido($this->jovem, 'novo', $variavel->id);
    marcarConcluido($this->jovem, 'novo', $substitutiva->id);

    $status = $this->service->statusBloco($this->jovem, $bloco->fresh());

    expect($status['status'])->toBe('Parcial')
        ->and($status['obrigatorias_concluidas'])->toBe(0)
        ->and($status['obrigatorias_necessarias'])->toBe(1);
});

it('calcula percentuais e pendencias do programa novo corretamente', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);

    $blocoCompleto = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Completo']);
    $itemCompleto = ItemNovo::create(['bloco_id' => $blocoCompleto->id, 'codigo' => 'A-001', 'descricao' => 'Item', 'tipo_acao' => 'Obrigatória']);
    marcarConcluido($this->jovem, 'novo', $itemCompleto->id);

    $blocoIncompleto = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Incompleto']);
    ItemNovo::create(['bloco_id' => $blocoIncompleto->id, 'codigo' => 'B-001', 'descricao' => 'Item', 'tipo_acao' => 'Obrigatória']);

    $percentual = $this->service->percentualNovo($this->jovem);
    expect($percentual['total'])->toBe(2)
        ->and($percentual['concluidos'])->toBe(1)
        ->and($percentual['percentual'])->toBe(50.0);

    $pendencias = $this->service->pendenciasNovo($this->jovem);
    expect($pendencias)->toHaveCount(1)
        ->and($pendencias[0]['bloco']->id)->toBe($blocoIncompleto->id)
        ->and($pendencias[0]['detalhe'])->toContain('Obrigatórias');
});

it('resumoNovo: variaveis_atingidas soma no maximo a quantidade minima do bloco, nao o total concluido', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco', 'quantidade_minima_variaveis' => 3]);

    $variaveis = collect(range(1, 5))->map(
        fn ($i) => ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "VAR-{$i}", 'descricao' => "Var {$i}", 'tipo_acao' => 'Variável'])
    );

    // conclui 4 das 5 variaveis (minimo exigido eh 3)
    foreach ($variaveis->take(4) as $variavel) {
        marcarConcluido($this->jovem, 'novo', $variavel->id);
    }

    $resumo = $this->service->resumoNovo($this->jovem);

    expect($resumo['variaveis_minimas_total'])->toBe(3)
        ->and($resumo['variaveis_atingidas'])->toBe(3); // nao 4
});

it('resumoNovo: soma corretamente Obrigatorias entre multiplos Blocos', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);

    $bloco1 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $obg1a = ItemNovo::create(['bloco_id' => $bloco1->id, 'codigo' => 'OBG-1A', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
    $obg1b = ItemNovo::create(['bloco_id' => $bloco1->id, 'codigo' => 'OBG-1B', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);

    $bloco2 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 2']);
    ItemNovo::create(['bloco_id' => $bloco2->id, 'codigo' => 'OBG-2A', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);

    marcarConcluido($this->jovem, 'novo', $obg1a->id);
    marcarConcluido($this->jovem, 'novo', $obg1b->id);
    // OBG-2A fica pendente

    $resumo = $this->service->resumoNovo($this->jovem);

    expect($resumo['obrigatorias_total'])->toBe(3)
        ->and($resumo['obrigatorias_concluidas'])->toBe(2)
        ->and($resumo['blocos_total'])->toBe(18);
});
