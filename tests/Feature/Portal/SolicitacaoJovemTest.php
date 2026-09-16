<?php

use App\Livewire\Portal\Progresso;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    $this->itemNovo = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('marca um item novo como solicitado sem concluir direto', function () {
    Livewire::test(Progresso::class)->call('solicitarNovo', $this->itemNovo->id);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();

    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->solicitado_em)->not->toBeNull()
        ->and($progresso->concluido)->toBeFalse();
});

it('nao faz nada ao solicitar um item que ja esta concluido', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => true,
        'data_conclusao' => now(),
    ]);

    Livewire::test(Progresso::class)->call('solicitarNovo', $this->itemNovo->id);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();

    expect($progresso->solicitado_pelo_jovem)->toBeFalse();
});
