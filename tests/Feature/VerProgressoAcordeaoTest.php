<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $this->competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    ItemAntigo::create(['competencia_id' => $this->competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item antigo pendente']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'descricao' => 'Intencionalidade do bloco', 'quantidade_minima_variaveis' => 1]);
    ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Ação obrigatória', 'tipo_acao' => 'Obrigatória']);
});

it('abre por padrao na aba do programa novo', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSet('abaAtiva', 'novo');
});

it('renderiza o bloco novo dentro de um acordeao fechado, identificavel pelo id', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSee('id="bloco-'.$this->bloco->id.'"', false)
        ->assertSee('Intencionalidade do bloco');
});

it('mostra o detalhe da pendencia direto dentro do acordeao do bloco, sem secao separada', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSee('Faltam 1 Ações Obrigatórias');
});

it('renderiza a competencia antiga dentro de um acordeao fechado, identificavel pelo id', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'antigo')
        ->assertSee('id="competencia-'.$this->competencia->id.'"', false);
});
