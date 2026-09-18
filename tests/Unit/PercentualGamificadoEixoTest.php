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

it('calcula o percentual gamificado de um eixo pela media dos blocos dele', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);

    $bloco1 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $item1 = ItemNovo::create(['bloco_id' => $bloco1->id, 'codigo' => 'B1-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $item1->id, 'concluido' => true, 'data_conclusao' => now()]);

    $bloco2 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 2']);
    ItemNovo::create(['bloco_id' => $bloco2->id, 'codigo' => 'B2-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);

    $eixo->load('blocos.itens');

    // Bloco 1: 100% (1 de 1 obrigatória concluída). Bloco 2: 0%. Média: 50%.
    expect($service->percentualGamificadoEixo($jovem, $eixo))->toBe(0.5);
});

it('retorna zero pra um eixo sem blocos', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Vazio']);
    $eixo->load('blocos');

    expect($service->percentualGamificadoEixo($jovem, $eixo))->toBe(0.0);
});
