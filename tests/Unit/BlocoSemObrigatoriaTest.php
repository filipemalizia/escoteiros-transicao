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

function cenarioBlocoSemObrigatoria(string $ramoNome = 'Sênior', int $variaveis = 5, int $minimo = 5): array
{
    $ramo = Ramo::create(['nome' => $ramoNome]);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Teste']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco sem fixa', 'quantidade_minima_variaveis' => $minimo]);

    $itens = [];
    foreach (range(1, $variaveis) as $i) {
        $itens[] = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "V-00{$i}", 'descricao' => "Variavel {$i}", 'tipo_acao' => 'Variável']);
    }

    return [$jovem, $bloco, $itens, new StatusProgressaoService];
}

function concluirItem(Jovem $jovem, ItemNovo $item, string $data = '2026-03-01'): void
{
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => $data]);
}

it('bloco sem obrigatoria nao aparece pela metade sem nenhuma acao feita', function () {
    [$jovem, $bloco, , $service] = cenarioBlocoSemObrigatoria();

    expect($service->percentualGamificadoBloco($jovem, $bloco))->toBe(0.0);
});

it('bloco sem obrigatoria usa so as variaveis na barra', function () {
    [$jovem, $bloco, $itens, $service] = cenarioBlocoSemObrigatoria();

    concluirItem($jovem, $itens[0]);
    concluirItem($jovem, $itens[1]);

    expect($service->percentualGamificadoBloco($jovem, $bloco))->toBe(0.4);
});

it('bloco com obrigatoria e variavel continua sendo a media das duas partes', function () {
    [$jovem, $bloco, $itens, $service] = cenarioBlocoSemObrigatoria(variaveis: 2, minimo: 2);
    $obrigatoria = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'O-001', 'descricao' => 'Fixa', 'tipo_acao' => 'Obrigatória']);

    concluirItem($jovem, $obrigatoria);

    expect($service->percentualGamificadoBloco($jovem, $bloco))->toBe(0.5);
});

it('bloco concluido (ex.: pela substitutiva) fica com a barra cheia', function () {
    [$jovem, $bloco, , $service] = cenarioBlocoSemObrigatoria();
    $substitutiva = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'S-001', 'descricao' => 'Substitutiva', 'tipo_acao' => 'Substitutiva']);

    concluirItem($jovem, $substitutiva);

    expect($service->statusBloco($jovem, $bloco)['status'])->toBe('Concluído')
        ->and($service->percentualGamificadoBloco($jovem, $bloco))->toBe(1.0);
});

it('o percentual geral nao ganha meio bloco de graca por bloco sem obrigatoria', function () {
    [$jovem, , , $service] = cenarioBlocoSemObrigatoria();

    expect($service->percentualGamificadoNovo($jovem)['percentual'])->toBe(0.0);
});

it('o texto do chefe diz que o bloco nao tem obrigatorias, em vez de Obrigatorias OK', function () {
    [$jovem, , , $service] = cenarioBlocoSemObrigatoria();

    $detalhe = $service->pendenciasNovo($jovem)[0]['detalhe'];

    expect($detalhe)->toContain('Faltam 5 Ações Variáveis (tem 0 de 5) ou 1 Substitutiva')
        ->and($detalhe)->toContain('Sem ações obrigatórias neste bloco')
        ->and($detalhe)->not->toContain('Obrigatórias OK');
});

it('o texto do chefe continua dizendo Obrigatorias OK quando o bloco tem obrigatorias e elas estao feitas', function () {
    [$jovem, $bloco, , $service] = cenarioBlocoSemObrigatoria();
    $obrigatoria = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'O-001', 'descricao' => 'Fixa', 'tipo_acao' => 'Obrigatória']);
    concluirItem($jovem, $obrigatoria);

    expect($service->pendenciasNovo($jovem)[0]['detalhe'])->toContain('Obrigatórias OK');
});

describe('Pioneiro', function () {
    it('a barra e 0% sem nada feito nem marcado e o texto explica o Projeto de Vida', function () {
        [$jovem, $bloco, , $service] = cenarioBlocoSemObrigatoria('Pioneiro');

        expect($service->percentualGamificadoBloco($jovem, $bloco))->toBe(0.0)
            ->and($service->pendenciasNovo($jovem)[0]['detalhe'])
            ->toContain('Nenhuma ação concluída ainda')
            ->toContain('Sem ações obrigatórias neste bloco');
    });

    it('a barra e a fracao entre o que ele fez e o que marcou como quero fazer', function () {
        [$jovem, $bloco, $itens, $service] = cenarioBlocoSemObrigatoria('Pioneiro');

        concluirItem($jovem, $itens[0], '2026-03-10');
        ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $itens[1]->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => '2026-03-01 10:00:00']);

        expect($service->percentualGamificadoBloco($jovem, $bloco))->toBe(0.5)
            ->and($service->pendenciasNovo($jovem)[0]['detalhe'])->toContain('Faltam 1 ações que o jovem marcou como "quero fazer"');
    });

    it('a barra enche quando o bloco conclui', function () {
        [$jovem, $bloco, $itens, $service] = cenarioBlocoSemObrigatoria('Pioneiro');

        concluirItem($jovem, $itens[0]);

        expect($service->percentualGamificadoBloco($jovem, $bloco))->toBe(1.0);
    });
});
