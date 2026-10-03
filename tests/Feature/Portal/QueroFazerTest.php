<?php

use App\Livewire\Portal\Catalogo;
use App\Livewire\Portal\EixoDetalhe;
use App\Livewire\Portal\QueroFazer;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
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

    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    $this->itemNovo = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);

    $this->itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Desafio especial']);
    $this->itemPersonalizado->jovens()->attach($this->jovem->id);

    $this->especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $this->especialidade->eixosNovos()->attach($this->eixo->id);
    $grupo = $this->especialidade->grupos()->create(['chave' => 'itens']);
    $this->itemEspecialidade = $grupo->itens()->create(['texto' => 'Montar barraca']);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('marca e desmarca um item novo como quero fazer', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->itemNovo->id);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->marcado_para_fazer)->toBeTrue()
        ->and($progresso->marcado_para_fazer_em)->not->toBeNull();

    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->itemNovo->id);

    expect($progresso->fresh()->marcado_para_fazer)->toBeFalse()
        ->and($progresso->fresh()->marcado_para_fazer_em)->toBeNull();
});

it('marca um item personalizado como quero fazer', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerPersonalizado', $this->itemPersonalizado->id);

    $progresso = ProgressoPersonalizado::where('jovem_id', $this->jovem->id)->where('item_personalizado_id', $this->itemPersonalizado->id)->first();
    expect($progresso->marcado_para_fazer)->toBeTrue();
});

it('marca um item de especialidade como quero fazer', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])->call('toggleQueroFazerEspecialidade', $this->itemEspecialidade->id);

    $progresso = ProgressoEspecialidade::where('jovem_id', $this->jovem->id)->where('especialidade_distintivo_item_id', $this->itemEspecialidade->id)->first();
    expect($progresso->marcado_para_fazer)->toBeTrue();
});

it('nao marca como quero fazer um item ja concluido', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => true,
        'data_conclusao' => now(),
    ]);

    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])->call('toggleQueroFazerNovo', $this->itemNovo->id);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->marcado_para_fazer)->toBeFalse();
});

it('lista agregada mostra os itens marcados dos 3 tipos', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);
    ProgressoPersonalizado::create(['jovem_id' => $this->jovem->id, 'item_personalizado_id' => $this->itemPersonalizado->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);
    ProgressoEspecialidade::create(['jovem_id' => $this->jovem->id, 'especialidade_distintivo_item_id' => $this->itemEspecialidade->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)
        ->assertSee('Item novo')
        ->assertSee('Desafio especial')
        ->assertSee('Montar barraca');
});

it('item marcado some da lista agregada assim que fica concluido', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
        'concluido' => true,
        'data_conclusao' => now(),
    ]);

    Livewire::test(QueroFazer::class)->assertDontSee('Item novo');
});

it('desmarca um item direto da lista agregada', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)->call('toggleQueroFazer', 'novo', $this->itemNovo->id);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->marcado_para_fazer)->toBeFalse();
});

it('pede confirmacao ao desmarcar um item direto da lista agregada', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)->assertSee('wire:confirm', false);
});

it('envia um item novo pra avaliacao direto da lista agregada', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)->call('enviarParaAvaliacao', 'novo', $this->itemNovo->id);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->solicitado_em)->not->toBeNull();
});

it('envia um item personalizado pra avaliacao direto da lista agregada', function () {
    ProgressoPersonalizado::create(['jovem_id' => $this->jovem->id, 'item_personalizado_id' => $this->itemPersonalizado->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)->call('enviarParaAvaliacao', 'personalizado', $this->itemPersonalizado->id);

    $progresso = ProgressoPersonalizado::where('jovem_id', $this->jovem->id)->where('item_personalizado_id', $this->itemPersonalizado->id)->first();
    expect($progresso->solicitado_pelo_jovem)->toBeTrue();
});

it('envia um item de especialidade pra avaliacao direto da lista agregada', function () {
    ProgressoEspecialidade::create(['jovem_id' => $this->jovem->id, 'especialidade_distintivo_item_id' => $this->itemEspecialidade->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)->call('enviarParaAvaliacao', 'especialidade', $this->itemEspecialidade->id);

    $progresso = ProgressoEspecialidade::where('jovem_id', $this->jovem->id)->where('especialidade_distintivo_item_id', $this->itemEspecialidade->id)->first();
    expect($progresso->solicitado_pelo_jovem)->toBeTrue();
});

it('nao envia pra avaliacao um item ja concluido', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'marcado_para_fazer' => true,
        'concluido' => true,
        'data_conclusao' => now(),
    ]);

    Livewire::test(QueroFazer::class)->call('enviarParaAvaliacao', 'novo', $this->itemNovo->id);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->solicitado_pelo_jovem)->toBeFalse();
});

it('clicar em enviar para avaliacao abre o modal em vez de enviar direto', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)
        ->call('abrirEnvioAvaliacao', 'novo', $this->itemNovo->id)
        ->assertSet('enviandoAvaliacaoTipo', 'novo')
        ->assertSet('enviandoAvaliacaoItemId', $this->itemNovo->id)
        ->assertSee('Enviar para avaliação');

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->solicitado_pelo_jovem)->toBeFalse();
});

it('fecha o modal de envio sem enviar ao cancelar', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)
        ->call('abrirEnvioAvaliacao', 'novo', $this->itemNovo->id)
        ->call('fecharEnvioAvaliacao')
        ->assertSet('enviandoAvaliacaoTipo', null)
        ->assertSet('enviandoAvaliacaoItemId', null);
});

it('salva a observacao opcional e fecha o modal ao confirmar o envio pela lista agregada', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)
        ->call('abrirEnvioAvaliacao', 'novo', $this->itemNovo->id)
        ->set("observacoesAvaliacao.novo.{$this->itemNovo->id}", 'Fiz com a ajuda do meu pai.')
        ->call('enviarAvaliacaoAtual')
        ->assertSet('enviandoAvaliacaoItemId', null);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->observacao_jovem)->toBe('Fiz com a ajuda do meu pai.');
});

it('mostra "aguardando avaliacao" em vez do botao de enviar quando o item ja foi solicitado', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    Livewire::test(QueroFazer::class)
        ->assertSee('Aguardando avaliação')
        ->assertDontSee('Enviar para avaliação');
});

it('define um prazo futuro pra um item marcado como quero fazer', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id, 'marcado_para_fazer' => true, 'marcado_para_fazer_em' => now()]);

    Livewire::test(QueroFazer::class)
        ->assertSee('Sem prazo definido')
        ->call('abrirEdicaoPrazo', 'novo', $this->itemNovo->id, null)
        ->set('editandoPrazoValor', '2026-12-25')
        ->call('salvarPrazo')
        ->assertSet('editandoPrazoTipo', null);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->data_alvo->toDateString())->toBe('2026-12-25');
});

it('mostra o prazo definido na listagem', function () {
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->itemEspecialidade->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
        'data_alvo' => '2026-12-25',
    ]);

    Livewire::test(QueroFazer::class)->assertSee('25/12/2026');
});

it('limpa o prazo quando o campo fica em branco', function () {
    ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $this->itemPersonalizado->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
        'data_alvo' => '2026-12-25',
    ]);

    Livewire::test(QueroFazer::class)
        ->call('abrirEdicaoPrazo', 'personalizado', $this->itemPersonalizado->id, '2026-12-25')
        ->set('editandoPrazoValor', '')
        ->call('salvarPrazo');

    $progresso = ProgressoPersonalizado::where('jovem_id', $this->jovem->id)->where('item_personalizado_id', $this->itemPersonalizado->id)->first();
    expect($progresso->data_alvo)->toBeNull();
});

it('nao define prazo pra um item que nao esta marcado como quero fazer', function () {
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovo->id]);

    Livewire::test(QueroFazer::class)
        ->call('abrirEdicaoPrazo', 'novo', $this->itemNovo->id, null)
        ->set('editandoPrazoValor', '2026-12-25')
        ->call('salvarPrazo');

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->data_alvo)->toBeNull();
});
