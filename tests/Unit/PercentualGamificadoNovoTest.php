<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('conta progresso parcial de um bloco, ao contrario do percentual oficial que so conta bloco 100%', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 2]);

    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-002', 'descricao' => 'Obg 2', 'tipo_acao' => 'Obrigatória']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-003', 'descricao' => 'Obg 3', 'tipo_acao' => 'Obrigatória']);
    $obrigatoria4 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-004', 'descricao' => 'Obg 4', 'tipo_acao' => 'Obrigatória']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-005', 'descricao' => 'Var 1', 'tipo_acao' => 'Variável']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-006', 'descricao' => 'Var 2', 'tipo_acao' => 'Variável']);

    // Marca 3 das 4 obrigatórias, nenhuma variável.
    foreach (['B1-001', 'B1-002', 'B1-003'] as $codigo) {
        $item = ItemNovo::where('codigo', $codigo)->first();
        ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);
    }

    $oficial = $service->percentualNovo($jovem);
    $gamificado = $service->percentualGamificadoNovo($jovem);

    expect($oficial['concluidos'])->toBe(0)
        ->and($oficial['percentual'])->toBe(0.0)
        ->and($gamificado['percentual'])->toBe(37.5);

    expect($obrigatoria4)->not->toBeNull();
});

it('bloco sem nenhuma variavel exigida conta so a parte obrigatoria no percentual gamificado', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);

    $obrigatoria1 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-002', 'descricao' => 'Obg 2', 'tipo_acao' => 'Obrigatória']);

    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $obrigatoria1->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);

    $gamificado = $service->percentualGamificadoNovo($jovem);

    expect($gamificado['percentual'])->toBe(50.0);
});

it('bloco sem nenhuma obrigatoria nem variavel conta 0% no percentual gamificado, nunca 100%', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco vazio']);

    expect($service->percentualGamificadoBloco($jovem, $bloco))->toBe(0.0);
});

it('calcula a data de conclusao de um bloco pela maior data entre obrigatorias e as variaveis mais antigas necessarias', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);

    $obrigatoria1 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    $obrigatoria2 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-002', 'descricao' => 'Obg 2', 'tipo_acao' => 'Obrigatória']);
    $variavel1 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-003', 'descricao' => 'Var 1', 'tipo_acao' => 'Variável']);
    $variavel2 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-004', 'descricao' => 'Var 2', 'tipo_acao' => 'Variável']);

    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $obrigatoria1->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $obrigatoria2->id, 'concluido' => true, 'data_conclusao' => '2026-01-15']);
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $variavel1->id, 'concluido' => true, 'data_conclusao' => '2026-01-05']);
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $variavel2->id, 'concluido' => true, 'data_conclusao' => '2026-01-20']);

    $data = $service->dataConclusaoBloco($jovem, $bloco);

    // Só 1 Variável era necessária: usa a mais antiga concluída (05/01), que
    // já não é a data que decide o fechamento do bloco (a última Obrigatória,
    // em 15/01, é mais tardia).
    expect($data->format('Y-m-d'))->toBe('2026-01-15');
});

it('usa a data da substitutiva quando foi por ela que o bloco fechou', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 2]);

    $obrigatoria1 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    $substitutiva = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-002', 'descricao' => 'Sub', 'tipo_acao' => 'Substitutiva']);

    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $obrigatoria1->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $substitutiva->id, 'concluido' => true, 'data_conclusao' => '2026-01-25']);

    $status = $service->statusBloco($jovem, $bloco);
    $data = $service->dataConclusaoBloco($jovem, $bloco);

    expect($status['status'])->toBe('Concluído')
        ->and($data->format('Y-m-d'))->toBe('2026-01-25');
});

it('calcula a data de conclusao de um bloco sem nenhuma obrigatoria, sem quebrar no merge de colecoes vazias', function () {
    // Regressão: um bloco sem Obrigatórias visíveis (ou sem nenhuma
    // concluída) deixa `$datasObrigatorias` como uma `Eloquent\Collection`
    // vazia (o auto-downgrade pra `Support\Collection` do `->map()` só
    // acontece quando o resultado tem pelo menos um item) — sem a correção,
    // `->merge()` nela tentava chamar `getKey()` num Carbon e explodia com
    // `Carbon\Exceptions\UnknownMethodException`.
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);

    $variavel = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Var 1', 'tipo_acao' => 'Variável']);
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $variavel->id, 'concluido' => true, 'data_conclusao' => '2026-01-05']);

    $status = $service->statusBloco($jovem, $bloco);
    $data = $service->dataConclusaoBloco($jovem, $bloco);

    expect($status['status'])->toBe('Concluído')
        ->and($data->format('Y-m-d'))->toBe('2026-01-05');
});

it('calcula a data de conclusao via substitutiva de um bloco sem nenhuma obrigatoria, sem quebrar no merge', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 2]);

    $substitutiva = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Sub', 'tipo_acao' => 'Substitutiva']);
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $substitutiva->id, 'concluido' => true, 'data_conclusao' => '2026-01-25']);

    $status = $service->statusBloco($jovem, $bloco);
    $data = $service->dataConclusaoBloco($jovem, $bloco);

    expect($status['status'])->toBe('Concluído')
        ->and($data->format('Y-m-d'))->toBe('2026-01-25');
});

it('retorna null pra data de conclusao de bloco ainda nao concluido', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);

    expect($service->dataConclusaoBloco($jovem, $bloco))->toBeNull();
});
