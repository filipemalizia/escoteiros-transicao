<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    $this->item = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);

    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);
});

it('mostra o aviso no topo da aba quando ha itens aguardando avaliacao', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSee('aguardando')
        ->assertSee('sua avaliação');
});

it('mostra a contagem de avaliacoes pendentes no acordeao do bloco', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSee('1 item aguardando avaliação');
});

it('nao mostra nenhum aviso quando nao ha solicitacoes pendentes', function () {
    ProgressoNovo::where('item_novo_id', $this->item->id)->update(['solicitado_pelo_jovem' => false, 'solicitado_em' => null]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertDontSee('aguardando sua avaliação')
        ->assertDontSee('item aguardando avaliação');
});
