<?php

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\Equivalencia;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Trava a otimização de N+1 feita em EquivalenciaCreditoService/
 * StatusProgressaoService (memoização + singleton) — sem ela, renderizar a
 * tela de um Eixo com o volume real do programa novo (18 blocos) chegava a
 * 2000+ queries numa única página. Não é pra ser um número exato e frágil,
 * só um teto generoso que estoura se o N+1 voltar.
 */
it('renderiza a tela do eixo com um numero razoavel de queries mesmo com os 18 blocos do programa novo', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $itensAntigos = [];
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    for ($i = 1; $i <= 20; $i++) {
        $itensAntigos[] = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => "ANT-{$i}", 'descricao' => "Item antigo {$i}"]);
    }

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Único']);
    $itensNovos = [];
    for ($b = 1; $b <= 18; $b++) {
        $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco {$b}", 'descricao' => 'Descrição do bloco', 'quantidade_minima_variaveis' => 2]);
        for ($i = 1; $i <= 5; $i++) {
            $tipo = $i <= 2 ? 'Obrigatória' : 'Variável';
            $itensNovos[] = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "B{$b}-{$i}", 'descricao' => "Item {$i} do bloco {$b}", 'tipo_acao' => $tipo]);
        }
    }

    foreach (array_slice($itensNovos, 0, 15) as $idx => $itemNovo) {
        Equivalencia::create([
            'item_antigo_id' => $itensAntigos[$idx % count($itensAntigos)]->id,
            'item_novo_id' => $itemNovo->id,
            'tipo_equivalencia' => '1-1',
        ]);
    }

    foreach (array_slice($itensNovos, 20, 10) as $itemNovo) {
        ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $itemNovo->id, 'concluido' => true, 'data_conclusao' => now()]);
    }

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    DB::enableQueryLog();
    $this->get(route('portal.eixos.show', $eixo))->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThan(500);
});
