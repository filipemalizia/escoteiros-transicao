<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\EtapaProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function criarBlocosConcluidos(Jovem $jovem, EixoNovo $eixo, int $quantidade): void
{
    foreach (range(1, $quantidade) as $i) {
        $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco {$i}"]);
        $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "B{$i}-001", 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
        ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => now()]);
    }
}

it('monta a trilha com as 4 etapas do Lobinho (4/8/13/18 blocos) mais o reconhecimento no final', function () {
    $service = new EtapaProgressaoService;
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);

    criarBlocosConcluidos($jovem, $eixo, 5);

    $trilha = $service->trilhaEtapaNovo($jovem);

    expect($trilha)->toHaveCount(5);

    $porLabel = collect($trilha)->keyBy('label');

    // Os cortes são cumulativos (sempre sobre os mesmos 18 blocos), então
    // o preenchimento de cada distintivo é `concluidos / corte_dele`, não
    // relativo ao corte anterior - os 5 blocos já concluídos contam a
    // favor de TODAS as etapas futuras, só que numa fração menor conforme
    // o corte fica mais distante (a "escada").
    expect($porLabel->keys()->all())->toBe(['Pata Tenra', 'Saltador', 'Rastreador', 'Caçador', 'Cruzeiro do Sul'])
        ->and($porLabel['Pata Tenra']['tipo'])->toBe('etapa')
        ->and($porLabel['Pata Tenra']['alcancado'])->toBeTrue()
        ->and($porLabel['Pata Tenra']['progresso'])->toBe(1.0)
        ->and($porLabel['Saltador']['alcancado'])->toBeFalse()
        ->and($porLabel['Saltador']['atual'])->toBeTrue()
        ->and($porLabel['Saltador']['faltam'])->toBe(3)
        ->and($porLabel['Saltador']['progresso'])->toBe(5 / 8)
        ->and($porLabel['Rastreador']['progresso'])->toBe(5 / 13)
        ->and($porLabel['Caçador']['progresso'])->toBe(5 / 18)
        ->and($porLabel['Cruzeiro do Sul']['tipo'])->toBe('reconhecimento')
        ->and($porLabel['Cruzeiro do Sul']['alcancado'])->toBeFalse()
        ->and($porLabel['Cruzeiro do Sul']['faltam'])->toBe(13);
});

it('monta a trilha com as etapas distintas do Senior (3) mais o reconhecimento', function () {
    $service = new EtapaProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);

    criarBlocosConcluidos($jovem, $eixo, 0);

    $trilha = $service->trilhaEtapaNovo($jovem);

    expect(collect($trilha)->pluck('label')->all())->toBe(['Escalada', 'Conquista', 'Azimute', 'Escoteiro da Pátria'])
        ->and($trilha[0]['atual'])->toBeTrue();
});

it('reconhecimento so fica alcancado quando os 18 blocos E os requisitos complementares estao satisfeitos', function () {
    $service = new EtapaProgressaoService;
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);

    criarBlocosConcluidos($jovem, $eixo, 18);

    $trilha = $service->trilhaEtapaNovo($jovem);
    $reconhecimento = collect($trilha)->firstWhere('tipo', 'reconhecimento');

    // 18 blocos batem o corte, mas os requisitos complementares (Desafio
    // pessoal / Avaliação dos pares) não foram marcados - por isso ainda
    // não "alcançado", mesmo com `faltam` zerado (não faltam mais blocos).
    // `progresso` é a média entre blocos (100%) e requisitos (0 de 2) =
    // 0.5 - regressão do bug em que ficava sempre 1.0 (todo preenchido)
    // assim que a última Etapa era alcançada, já que o corte de blocos da
    // Reconhecimento é sempre igual ao da última Etapa.
    expect($reconhecimento['faltam'])->toBe(0)
        ->and($reconhecimento['alcancado'])->toBeFalse()
        ->and($reconhecimento['atual'])->toBeTrue()
        ->and($reconhecimento['progresso'])->toBe(0.5);
});

it('preenche o reconhecimento por completo quando blocos e requisitos complementares estao 100%', function () {
    $service = new EtapaProgressaoService;
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);

    criarBlocosConcluidos($jovem, $eixo, 18);

    foreach (['lobinho_novo_desafio_pessoal', 'lobinho_novo_avaliacao_pares'] as $chave) {
        $jovem->requisitosComplementares()->create(['chave' => $chave, 'tipo' => 'booleano', 'valor_booleano' => true]);
    }

    $trilha = $service->trilhaEtapaNovo($jovem->fresh());
    $reconhecimento = collect($trilha)->firstWhere('tipo', 'reconhecimento');

    expect($reconhecimento['alcancado'])->toBeTrue()
        ->and($reconhecimento['progresso'])->toBe(1.0)
        ->and($reconhecimento['data_alcancado'])->not->toBeNull();
});

it('preenche a data em que cada corte de etapa foi alcancado, pela ordem cronologica de conclusao dos blocos', function () {
    $service = new EtapaProgressaoService;
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);

    // 4 blocos concluídos em datas fora de ordem de propósito - Pata Tenra
    // (corte 4) deve usar a data do 4º concluído cronologicamente, não a
    // data de cadastro do bloco.
    $datas = ['2026-01-20', '2026-01-05', '2026-01-15', '2026-01-10'];

    foreach ($datas as $i => $data) {
        $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco {$i}"]);
        $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "B{$i}-001", 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
        ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => $data]);
    }

    $data = $service->dataCorteEtapaNovo($jovem, 4);

    expect($data->format('Y-m-d'))->toBe('2026-01-20');

    $trilha = $service->trilhaEtapaNovo($jovem);
    $porLabel = collect($trilha)->keyBy('label');

    expect($porLabel['Pata Tenra']['data_alcancado']->format('Y-m-d'))->toBe('2026-01-20')
        ->and($porLabel['Saltador']['data_alcancado'])->toBeNull();
});

it('retorna lista vazia quando o ramo nao tem cortes de etapa definidos', function () {
    $service = new EtapaProgressaoService;
    $ramo = Ramo::create(['nome' => 'Ramo Desconhecido']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);

    expect($service->trilhaEtapaNovo($jovem))->toBe([]);
});
