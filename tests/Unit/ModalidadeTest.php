<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\Equipe;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new StatusProgressaoService;
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco 1']);

    $this->itemBasico = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item básico', 'tipo_acao' => 'Obrigatória']);
    $this->itemMar = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-002', 'descricao' => 'Item do Mar', 'tipo_acao' => 'Obrigatória', 'modalidade' => 'Mar']);
});

it('jovem sem equipe cai na modalidade Básica por padrão', function () {
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id]);

    expect($jovem->modalidade())->toBe('Básica');
});

it('ao mudar a equipe do jovem, o ramo dele acompanha automaticamente o da nova equipe', function () {
    $outroRamo = Ramo::create(['nome' => 'Pioneiro']);
    $equipeDoOutroRamo = Equipe::create(['ramo_id' => $outroRamo->id, 'nome' => 'Equipe Pioneiro']);

    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id]);

    $jovem->update(['equipe_id' => $equipeDoOutroRamo->id]);

    expect($jovem->fresh()->ramo_atual_id)->toBe($outroRamo->id);
});

it('desvincular a equipe do jovem nao mexe no ramo atual dele', function () {
    $equipe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe A']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $equipe->id]);

    $jovem->update(['equipe_id' => null]);

    expect($jovem->fresh()->ramo_atual_id)->toBe($this->ramo->id);
});

it('jovem herda a modalidade da propria equipe', function () {
    $equipeMar = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Tropa do Mar', 'modalidade' => 'Mar']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $equipeMar->id]);

    expect($jovem->modalidade())->toBe('Mar');
});

it('itensVisiveisDoBloco esconde item de outra modalidade, mas sempre mostra os basicos', function () {
    $jovemBasico = Jovem::create(['nome' => 'Jovem Básico', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id]);
    $equipeMar = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Tropa do Mar', 'modalidade' => 'Mar']);
    $jovemMar = Jovem::create(['nome' => 'Jovem do Mar', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $equipeMar->id]);

    $itensParaBasico = $this->service->itensVisiveisDoBloco($jovemBasico, $this->bloco);
    $itensParaMar = $this->service->itensVisiveisDoBloco($jovemMar, $this->bloco);

    expect($itensParaBasico->pluck('id')->all())->toBe([$this->itemBasico->id])
        ->and($itensParaMar->pluck('id')->sort()->values()->all())->toBe([$this->itemBasico->id, $this->itemMar->id]);
});

it('bloco so fecha por causa do item do Mar pro jovem que e da modalidade Mar', function () {
    $jovemBasico = Jovem::create(['nome' => 'Jovem Básico', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id]);
    $equipeMar = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Tropa do Mar', 'modalidade' => 'Mar']);
    $jovemMar = Jovem::create(['nome' => 'Jovem do Mar', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $equipeMar->id]);

    // Só marca o item básico como concluído pros dois.
    ProgressoNovo::create(['jovem_id' => $jovemBasico->id, 'item_novo_id' => $this->itemBasico->id, 'concluido' => true, 'data_conclusao' => now()]);
    ProgressoNovo::create(['jovem_id' => $jovemMar->id, 'item_novo_id' => $this->itemBasico->id, 'concluido' => true, 'data_conclusao' => now()]);

    // O básico já concluiu tudo que é dele (item Mar não conta contra ele).
    expect($this->service->statusBloco($jovemBasico, $this->bloco)['status'])->toBe('Concluído');

    // O do Mar ainda tem o item Mar pendente, então o bloco não fecha.
    expect($this->service->statusBloco($jovemMar, $this->bloco)['status'])->toBe('Parcial');
});
