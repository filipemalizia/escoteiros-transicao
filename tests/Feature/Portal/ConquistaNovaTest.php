<?php

use App\Livewire\Portal\Inicio;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
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

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('dispara confete quando o jovem tem uma conquista e portal_visitado_em estava zerado', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);

    expect($this->jovem->portal_visitado_em)->toBeNull();

    Livewire::test(Inicio::class)
        ->assertDispatched('conquista-nova')
        ->assertSet('modalConquistasAberto', true)
        ->assertSee('Bloco 1');

    expect($this->jovem->fresh()->portal_visitado_em->format('Y-m-d'))->toBe('2026-01-10');
});

it('fecha o modal de conquistas sem reabrir ao recarregar (ja foi persistido)', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);

    Livewire::test(Inicio::class)
        ->assertSet('modalConquistasAberto', true)
        ->call('fecharModalConquistas')
        ->assertSet('modalConquistasAberto', false);

    Livewire::test(Inicio::class)->assertSet('modalConquistasAberto', false);
});

it('nao dispara confete de novo numa segunda visita sem nada de novo concluido', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);

    Livewire::test(Inicio::class)->assertDispatched('conquista-nova');
    Livewire::test(Inicio::class)->assertNotDispatched('conquista-nova');
});

it('dispara confete de novo quando uma conquista mais recente aparece depois da ultima vista', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco1 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $item1 = ItemNovo::create(['bloco_id' => $bloco1->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item1->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);

    Livewire::test(Inicio::class)->assertDispatched('conquista-nova');

    $bloco2 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 2']);
    $item2 = ItemNovo::create(['bloco_id' => $bloco2->id, 'codigo' => 'B2-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item2->id, 'concluido' => true, 'data_conclusao' => '2026-01-20']);

    // Em produção cada visita é uma requisição HTTP nova, com seu próprio
    // singleton (ver docblock de StatusProgressaoService); dentro de um
    // mesmo teste Pest, as duas visitas simuladas compartilham o container,
    // então precisa esquecer o cache manualmente pra simular a 2ª visita de
    // verdade (mesma regra que já vale pra qualquer escrita em progresso).
    app(StatusProgressaoService::class)->limparCache();

    Livewire::test(Inicio::class)->assertDispatched('conquista-nova');

    expect($this->jovem->fresh()->portal_visitado_em->format('Y-m-d'))->toBe('2026-01-20');
});

it('nao dispara confete quando o jovem ainda nao tem nenhuma conquista', function () {
    Livewire::test(Inicio::class)->assertNotDispatched('conquista-nova');

    expect($this->jovem->fresh()->portal_visitado_em)->toBeNull();
});
