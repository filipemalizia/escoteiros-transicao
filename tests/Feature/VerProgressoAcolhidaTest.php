<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\Jovem;
use App\Models\ProgressoAcolhida;
use App\Models\Ramo;
use App\Models\User;
use App\Services\AcolhidaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);

    $this->ramo = Ramo::create(['nome' => 'Pioneiro']);
    $this->jovem = Jovem::create(['nome' => 'Pioneiro de Teste', 'data_nascimento' => '2007-01-01', 'ramo_atual_id' => $this->ramo->id]);
});

function paginaDoJovem(Jovem $jovem)
{
    return Livewire::test(VerProgresso::class, ['record' => $jovem->getKey()]);
}

it('mostra a secao de Periodo de Acolhida com os itens do ramo e a Promessa pendente', function () {
    paginaDoJovem($this->jovem)
        ->assertSee('Período de Acolhida')
        ->assertSee('0 de 4')
        ->assertSee('Promessa pendente')
        ->assertSee('Participar das atividades do clã');
});

it('o chefe marca e desmarca um item da acolhida, e o contador acompanha', function () {
    paginaDoJovem($this->jovem)->call('toggleAcolhida', 1)->assertSee('1 de 4');

    $progresso = ProgressoAcolhida::where('jovem_id', $this->jovem->id)->where('ordem', 1)->first();
    expect($progresso->concluido)->toBeTrue()
        ->and($progresso->data_conclusao->isToday())->toBeTrue()
        ->and($progresso->registrado_por_id)->toBe($this->admin->id)
        ->and($progresso->ramo_id)->toBe($this->ramo->id);

    paginaDoJovem($this->jovem)->call('toggleAcolhida', 1);
    expect($progresso->fresh()->concluido)->toBeFalse();
});

it('mostra 100% ao lado do titulo quando todos os itens estao concluidos', function () {
    $pagina = paginaDoJovem($this->jovem);

    foreach (range(1, 3) as $ordem) {
        $pagina->call('toggleAcolhida', $ordem);
    }
    $pagina->assertDontSee('100%');

    $pagina->call('toggleAcolhida', 4)->assertSee('100%');
});

it('ignora um item que nao existe na lista do ramo', function () {
    paginaDoJovem($this->jovem)->call('toggleAcolhida', 99);

    expect(ProgressoAcolhida::count())->toBe(0);
});

it('o chefe aprova ou recusa o pedido de avaliacao do jovem', function () {
    ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => 2, 'solicitado_pelo_jovem' => true, 'solicitado_em' => now(), 'observacao_jovem' => 'Ja uso o lenco']);

    paginaDoJovem($this->jovem)
        ->assertSee('Aguardando avaliação')
        ->call('abrirAvaliacao', 'acolhida', 2)
        ->assertSee('Ja uso o lenco')
        ->call('rejeitarAvaliacaoAtual');

    $progresso = ProgressoAcolhida::first();
    expect($progresso->solicitado_pelo_jovem)->toBeFalse()->and($progresso->concluido)->toBeFalse();

    $progresso->update(['solicitado_pelo_jovem' => true, 'solicitado_em' => now()]);

    paginaDoJovem($this->jovem)->call('abrirAvaliacao', 'acolhida', 2)->call('confirmarAvaliacaoAtual');

    expect($progresso->fresh()->concluido)->toBeTrue()->and($progresso->fresh()->solicitado_pelo_jovem)->toBeFalse();
});

it('o chefe edita a data de conclusao de um item da acolhida', function () {
    ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => 1, 'concluido' => true, 'data_conclusao' => '2026-03-01']);

    paginaDoJovem($this->jovem)
        ->call('abrirEdicaoData', 'acolhida', 1, '2026-03-01')
        ->set('editandoDataValor', '2026-02-10')
        ->call('salvarEdicaoData');

    expect(ProgressoAcolhida::first()->data_conclusao->toDateString())->toBe('2026-02-10');
});

it('o chefe registra e remove a data da Promessa pela secao', function () {
    paginaDoJovem($this->jovem)
        ->set('dataPromessa', '2026-05-10')
        ->call('salvarDataPromessa')
        ->assertHasNoErrors()
        ->assertSee('Promessa em 10/05/2026');

    expect($this->jovem->fresh()->promessaRealizada())->toBeTrue();

    paginaDoJovem($this->jovem)->set('dataPromessa', '')->call('salvarDataPromessa');

    expect($this->jovem->fresh()->data_promessa)->toBeNull();
});

it('nao aceita data de Promessa no futuro nem invalida', function () {
    paginaDoJovem($this->jovem)
        ->set('dataPromessa', now()->addDays(3)->toDateString())
        ->call('salvarDataPromessa')
        ->assertHasErrors('dataPromessa');

    paginaDoJovem($this->jovem)->set('dataPromessa', 'abc')->call('salvarDataPromessa')->assertHasErrors('dataPromessa');

    expect($this->jovem->fresh()->data_promessa)->toBeNull();
});

it('avisa quando a Promessa do Lobinho nao vale para o ramo atual', function () {
    $lobinho = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem->update(['ramo_atual_id' => $lobinho->id, 'data_promessa' => '2025-01-10']);
    $this->jovem->update(['ramo_atual_id' => $this->ramo->id]);

    paginaDoJovem($this->jovem->fresh())
        ->assertSee('feita no Ramo Lobinho, não vale para este ramo')
        ->assertSee('Registre a Promessa Escoteira')
        ->assertSee('Promessa pendente');
});

it('a Promessa Escoteira feita em outro ramo escoteiro continua valendo e nao mostra aviso', function () {
    $escoteiro = Ramo::create(['nome' => 'Escoteiro']);
    $this->jovem->update(['ramo_atual_id' => $escoteiro->id, 'data_promessa' => '2025-01-10']);
    $this->jovem->update(['ramo_atual_id' => $this->ramo->id]);

    paginaDoJovem($this->jovem->fresh())
        ->assertSee('Promessa em 10/01/2025')
        ->assertDontSee('não vale para este ramo');
});

it('a acolhida nao bloqueia a marcacao de progresso de quem ainda nao fez a Promessa', function () {
    expect($this->jovem->promessaRealizada())->toBeFalse();

    paginaDoJovem($this->jovem)->call('toggleAcolhida', 1)->assertOk();
    expect(app(AcolhidaService::class)->resumo($this->jovem)['concluidos'])->toBe(1);
});
