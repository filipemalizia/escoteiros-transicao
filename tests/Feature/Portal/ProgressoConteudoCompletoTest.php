<?php

use App\Livewire\Portal\Progresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EquivalenciaBloco;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
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

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $this->itemAntigo = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item antigo vinculado ao bloco']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item novo de teste', 'tipo_acao' => 'Obrigatória']);

    EquivalenciaBloco::create(['item_antigo_id' => $this->itemAntigo->id, 'bloco_novo_id' => $this->bloco->id]);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('mostra o bloco de reconhecimento com o resumo do programa novo', function () {
    Livewire::test(Progresso::class)
        ->assertSee('Reconhecimento:')
        ->assertSee('Blocos concluídos: 0 de 18');
});

it('mostra o tipo de acao do item dentro do bloco', function () {
    Livewire::test(Progresso::class)
        ->assertSee('Obrigatória');
});

it('mostra os itens do programa antigo vinculados ao bloco por equivalencia de bloco', function () {
    Livewire::test(Progresso::class)
        ->assertSee('Item antigo vinculado ao bloco')
        ->assertSee('também contam como Ação Variável');
});
