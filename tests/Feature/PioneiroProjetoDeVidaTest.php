<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Livewire\Portal\EixoDetalhe;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Models\User;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Pioneiro']);
    $this->jovem = Jovem::create([
        'nome' => 'Pioneiro de Teste',
        'registro' => '654321',
        'data_nascimento' => '2007-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);
    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Teste']);

    // Bloco sem nenhuma Ação Fixa e com 3 variáveis; o mínimo cadastrado (2)
    // não vale pro Pioneiro.
    $this->bloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco sem fixa', 'quantidade_minima_variaveis' => 2]);
    $this->v1 = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'P-001', 'descricao' => 'Variavel 1', 'tipo_acao' => 'Variável']);
    $this->v2 = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'P-002', 'descricao' => 'Variavel 2', 'tipo_acao' => 'Variável']);
    $this->v3 = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'P-003', 'descricao' => 'Variavel 3', 'tipo_acao' => 'Variável']);

    $this->status = fn () => app(StatusProgressaoService::class)->statusBloco($this->jovem, $this->bloco)['status'];
});

function registrarProgressoPioneiro(Jovem $jovem, ItemNovo $item, array $atributos): ProgressoNovo
{
    app(StatusProgressaoService::class)->limparCache();

    return ProgressoNovo::updateOrCreate(['jovem_id' => $jovem->id, 'item_novo_id' => $item->id], $atributos);
}

it('bloco sem acao fixa nao nasce concluido e conclui com uma unica variavel feita', function () {
    expect(($this->status)())->toBe('Pendente');

    registrarProgressoPioneiro($this->jovem, $this->v1, ['concluido' => true, 'data_conclusao' => '2026-03-01']);

    expect(($this->status)())->toBe('Concluído');
});

it('item marcado como quero fazer e ainda nao feito segura o bloco', function () {
    registrarProgressoPioneiro($this->jovem, $this->v1, ['concluido' => true, 'data_conclusao' => '2026-03-01']);
    registrarProgressoPioneiro($this->jovem, $this->v2, ['marcado_para_fazer' => true, 'marcado_para_fazer_em' => '2026-02-20 10:00:00']);

    expect(($this->status)())->toBe('Parcial');

    registrarProgressoPioneiro($this->jovem, $this->v2, ['concluido' => true, 'data_conclusao' => '2026-03-10', 'marcado_para_fazer' => false]);

    expect(($this->status)())->toBe('Concluído');
});

it('marcar quero fazer depois do bloco concluido nao reabre o bloco', function () {
    registrarProgressoPioneiro($this->jovem, $this->v1, ['concluido' => true, 'data_conclusao' => '2026-03-01']);
    registrarProgressoPioneiro($this->jovem, $this->v2, ['marcado_para_fazer' => true, 'marcado_para_fazer_em' => '2026-03-05 10:00:00']);

    expect(($this->status)())->toBe('Concluído');
});

it('outros ramos continuam usando a quantidade minima de variaveis', function () {
    $ramoSenior = Ramo::create(['nome' => 'Sênior']);
    $this->jovem->update(['ramo_atual_id' => $ramoSenior->id]);
    $this->jovem->unsetRelation('ramoAtual');

    registrarProgressoPioneiro($this->jovem->fresh(), $this->v1, ['concluido' => true, 'data_conclusao' => '2026-03-01']);

    expect(app(StatusProgressaoService::class)->statusBloco($this->jovem->fresh(), $this->bloco)['status'])->toBe('Parcial');
});

it('dentro de 24h o pioneiro desmarca sozinho o quero fazer', function () {
    $this->post(route('portal.login'), ['registro' => '654321', 'data_nascimento' => '2007-05-20']);

    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->v1->id);
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->v1->id);

    expect(ProgressoNovo::first()->marcado_para_fazer)->toBeFalse();
});

it('depois de 24h o pioneiro so consegue pedir pra desmarcar e o chefe decide', function () {
    $this->post(route('portal.login'), ['registro' => '654321', 'data_nascimento' => '2007-05-20']);
    $progresso = registrarProgressoPioneiro($this->jovem, $this->v1, ['marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()->subDays(3)]);

    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->v1->id);

    expect($progresso->fresh()->marcado_para_fazer)->toBeTrue()
        ->and($progresso->fresh()->desmarcar_solicitado_em)->not->toBeNull();

    // Tocar de novo cancela o pedido, sem desmarcar.
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->v1->id);
    expect($progresso->fresh()->desmarcar_solicitado_em)->toBeNull()
        ->and($progresso->fresh()->marcado_para_fazer)->toBeTrue();

    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->v1->id);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    // Chefe recusa: segue marcado e sem pedido.
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('recusarPedidoDesmarcar', 'novo', $this->v1->id);
    expect($progresso->fresh()->marcado_para_fazer)->toBeTrue()
        ->and($progresso->fresh()->desmarcar_solicitado_em)->toBeNull();

    // Chefe aceita: desmarca e limpa o pedido.
    $progresso->update(['desmarcar_solicitado_em' => now()]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('desmarcarQueroFazer', 'novo', $this->v1->id);
    expect($progresso->fresh()->marcado_para_fazer)->toBeFalse()
        ->and($progresso->fresh()->desmarcar_solicitado_em)->toBeNull();
});

it('em outros ramos o quero fazer continua livre mesmo depois de 24h', function () {
    $ramoSenior = Ramo::create(['nome' => 'Sênior']);
    $this->jovem->update(['ramo_atual_id' => $ramoSenior->id]);
    $eixoSenior = EixoNovo::create(['ramo_id' => $ramoSenior->id, 'nome' => 'Eixo Senior']);

    $this->post(route('portal.login'), ['registro' => '654321', 'data_nascimento' => '2007-05-20']);
    $progresso = registrarProgressoPioneiro($this->jovem, $this->v1, ['marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()->subDays(3)]);

    Livewire::test(EixoDetalhe::class, ['eixo' => $eixoSenior])->call('toggleQueroFazerNovo', $this->v1->id);

    expect($progresso->fresh()->marcado_para_fazer)->toBeFalse();
});

it('mostra ao chefe ha quantos dias o item esta marcado e o pedido de desmarcar', function () {
    registrarProgressoPioneiro($this->jovem, $this->v1, [
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now()->subDays(5),
        'desmarcar_solicitado_em' => now(),
    ]);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSee('quero fazer há 5 dias')
        ->assertSee('Pediu para desmarcar');
});
