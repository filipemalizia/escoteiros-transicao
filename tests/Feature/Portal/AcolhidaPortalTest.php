<?php

use App\Livewire\Portal\Inicio;
use App\Livewire\Portal\Revisao;
use App\Models\Jovem;
use App\Models\ProgressoAcolhida;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Escoteiro']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2013-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $this->post(route('portal.login'), ['registro' => '123456', 'data_nascimento' => '2013-05-20']);
});

it('mostra o Periodo de Acolhida na tela inicial com os itens do ramo', function () {
    Livewire::test(Inicio::class)
        ->assertSee('Período de Acolhida')
        ->assertSee('0 de 7')
        ->assertSee('Utilizar o lenço escoteiro, saber o seu significado');
});

it('enquanto a Promessa nao foi registrada a acolhida aparece antes do progresso', function () {
    $html = Livewire::test(Inicio::class)->html();

    expect(strpos($html, 'Período de Acolhida'))->toBeLessThan(strpos($html, 'blocos concluídos'));

    $this->jovem->update(['data_promessa' => '2026-05-10']);

    $html = Livewire::test(Inicio::class)->html();

    expect(strpos($html, 'Período de Acolhida'))->toBeGreaterThan(strpos($html, 'blocos concluídos'))
        ->and($html)->toContain('Promessa em 10/05/2026');
});

it('o jovem envia um item da acolhida para avaliacao com observacao', function () {
    Livewire::test(Inicio::class)
        ->call('abrirEnvioAcolhida', 2)
        ->assertSee('Enviar para avaliação')
        ->set('observacoesAcolhida.2', 'Ja uso o lenco nas atividades')
        ->call('enviarAcolhida');

    $progresso = ProgressoAcolhida::where('jovem_id', $this->jovem->id)->where('ordem', 2)->first();

    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->concluido)->toBeFalse()
        ->and($progresso->observacao_jovem)->toBe('Ja uso o lenco nas atividades')
        ->and($progresso->ramo_id)->toBe($this->ramo->id)
        ->and(app(StatusProgressaoService::class)->contagemAguardandoRevisao($this->jovem))->toBe(1);
});

it('o item enviado aparece na Revisao do jovem', function () {
    ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => 3, 'solicitado_pelo_jovem' => true, 'solicitado_em' => now(), 'observacao_jovem' => 'Sei os apitos']);

    Livewire::test(Revisao::class)
        ->assertSee('Reconhecer e responder aos apitos')
        ->assertSee('Período de Acolhida')
        ->assertSee('Sei os apitos');
});

it('nao reenvia item ja concluido nem ordem que nao existe', function () {
    ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => 1, 'concluido' => true, 'data_conclusao' => '2026-03-01']);

    Livewire::test(Inicio::class)->call('abrirEnvioAcolhida', 1)->call('enviarAcolhida');
    Livewire::test(Inicio::class)->call('abrirEnvioAcolhida', 99)->call('enviarAcolhida');

    expect(ProgressoAcolhida::count())->toBe(1)
        ->and(ProgressoAcolhida::first()->solicitado_pelo_jovem)->toBeFalse();
});

it('mostra 100% quando o chefe concluiu todos os itens', function () {
    foreach (range(1, 7) as $ordem) {
        ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => $ordem, 'concluido' => true, 'data_conclusao' => '2026-03-01']);
    }

    Livewire::test(Inicio::class)->assertSee('100%');
});

it('o jovem nao ve a acolhida de um ramo que nao tem itens cadastrados', function () {
    $this->jovem->update(['ramo_atual_id' => Ramo::create(['nome' => 'Ramo Desconhecido'])->id]);

    Livewire::test(Inicio::class)->assertDontSee('Período de Acolhida');
});
