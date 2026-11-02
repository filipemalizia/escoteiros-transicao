<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\EquivalenciaCreditoService;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco', 'quantidade_minima_variaveis' => 1]);
    $this->itens = collect(range(1, 12))->map(fn ($i) => ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => "I-{$i}", 'descricao' => "Item {$i}", 'tipo_acao' => $i <= 3 ? 'Obrigatória' : 'Variável']));
    $this->jovem = Jovem::create(['nome' => 'Jovem', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id]);
});

it('le o progresso do jovem uma vez, nao uma vez por item', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itens[0]->id, 'concluido' => true]);
    $servico = new EquivalenciaCreditoService;

    DB::enableQueryLog();
    $concluidos = $this->itens->map(fn (ItemNovo $item) => $servico->itemNovoConcluido($this->jovem, $item))->all();
    $consultasAoProgresso = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'progresso_novo'))->count();

    expect($concluidos[0])->toBeTrue()
        ->and($concluidos[1])->toBeFalse()
        ->and($consultasAoProgresso)->toBe(1);
});

it('reaproveita as equivalencias de um item entre jovens diferentes', function () {
    $outro = Jovem::create(['nome' => 'Outro', 'data_nascimento' => '2010-02-02', 'ramo_atual_id' => $this->ramo->id]);
    $servico = new EquivalenciaCreditoService;

    $servico->itemNovoConcluido($this->jovem, $this->itens[0]);

    DB::enableQueryLog();
    $servico->itemNovoConcluido($outro, $this->itens[0]);
    $consultasAEquivalencias = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'equivalencia'))->count();

    expect($consultasAEquivalencias)->toBe(0);
});

it('limparCache faz o servico enxergar o progresso novo', function () {
    $servico = new EquivalenciaCreditoService;

    expect($servico->itemNovoConcluido($this->jovem, $this->itens[1]))->toBeFalse();

    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itens[1]->id, 'concluido' => true]);
    $servico->limparCache();

    expect($servico->itemNovoConcluido($this->jovem, $this->itens[1]))->toBeTrue();
});

it('os blocos do ramo sao lidos uma vez e o cache e esvaziado no limparCache', function () {
    $servico = new StatusProgressaoService;

    DB::enableQueryLog();
    $servico->blocosNovosDoRamo($this->ramo->id);
    $primeira = count(DB::getQueryLog());
    $servico->blocosNovosDoRamo($this->ramo->id);
    $segunda = count(DB::getQueryLog()) - $primeira;

    expect($segunda)->toBe(0);

    BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Novo bloco']);
    expect($servico->blocosNovosDoRamo($this->ramo->id))->toHaveCount(1);

    $servico->limparCache();
    expect($servico->blocosNovosDoRamo($this->ramo->id))->toHaveCount(2);
});
