<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('itensVisiveisDoBloco ordena por tipo de acao (obrigatoria, variavel, substitutiva) e depois por codigo', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);

    // Criados fora de ordem de propósito, pra provar que a ordenação não
    // depende da ordem de inserção nem do id.
    $substitutiva = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-005', 'descricao' => 'Sub', 'tipo_acao' => 'Substitutiva']);
    $variavel2 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-004', 'descricao' => 'Var 2', 'tipo_acao' => 'Variável']);
    $obrigatoria2 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-002', 'descricao' => 'Obg 2', 'tipo_acao' => 'Obrigatória']);
    $variavel1 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-003', 'descricao' => 'Var 1', 'tipo_acao' => 'Variável']);
    $obrigatoria1 = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);

    $ordem = $service->itensVisiveisDoBloco($jovem, $bloco)->pluck('id')->all();

    expect($ordem)->toBe([
        $obrigatoria1->id,
        $obrigatoria2->id,
        $variavel1->id,
        $variavel2->id,
        $substitutiva->id,
    ]);
});
