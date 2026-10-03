<?php

use App\Livewire\Portal\BuscaGeral;
use App\Livewire\Portal\Inicio;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
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
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);

    $this->especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $this->especialidade->eixosNovos()->attach($eixo->id);

    $this->insignia = EspecialidadeDistintivo::create(['nome' => 'Mensageiros da Paz', 'tipo' => 'Insígnia', 'estrutura' => 'atividades_temas']);
    $this->insignia->eixosNovos()->attach($eixo->id);

    $this->itemNovo = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B-001', 'descricao' => 'Acender uma fogueira', 'tipo_acao' => 'Obrigatória']);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('nao mostra nada sem busca', function () {
    Livewire::test(BuscaGeral::class)
        ->assertDontSee('Acampamento')
        ->assertDontSee('Itens de Progressão');
});

it('encontra especialidades e insignias juntas, independente do tipo', function () {
    Livewire::test(BuscaGeral::class)
        ->set('busca', 'acampa')
        ->assertSeeText('Acampamento');

    Livewire::test(BuscaGeral::class)
        ->set('busca', 'mensageiros')
        ->assertSeeText('Mensageiros da Paz');
});

it('encontra itens de progressao junto com as especialidades', function () {
    Livewire::test(BuscaGeral::class)
        ->set('busca', 'fogueira')
        ->assertSee('Itens de Progressão')
        ->assertSeeText('Acender uma fogueira');
});

it('busca geral ignora acentos, pra especialidades e itens de progressao', function () {
    ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B-002', 'descricao' => 'Organizar uma excursão à praia', 'tipo_acao' => 'Variável']);

    Livewire::test(BuscaGeral::class)
        ->set('busca', 'excursao praia')
        ->assertSeeText('Organizar uma excursão à praia');
});

it('tem um icone de busca no inicio levando pra rota de busca geral', function () {
    Livewire::test(Inicio::class)->assertSee(route('portal.busca'), false);
});

it('o link de uma especialidade encontrada ja leva com o modal pra abrir direto', function () {
    Livewire::test(BuscaGeral::class)
        ->set('busca', 'acampa')
        ->assertSee(route('portal.catalogo', 'especialidades', ['q' => 'acampa', 'abrir' => $this->especialidade->id]), false);
});

it('o link de abrir bloco leva pro eixo com o bloco e o termo de busca na url', function () {
    Livewire::test(BuscaGeral::class)
        ->set('busca', 'fogueira')
        ->assertSee(route('portal.eixos.show', $this->itemNovo->bloco->eixo, ['bloco' => $this->bloco->id, 'q' => 'fogueira']), false);
});

it('abre o modal de enviar para avaliacao a partir do resultado de progressao', function () {
    Livewire::test(BuscaGeral::class)
        ->set('busca', 'fogueira')
        ->call('abrirEnvioAvaliacao', $this->itemNovo->id)
        ->assertSet('enviandoAvaliacaoItemId', $this->itemNovo->id)
        ->assertSee('Enviar para avaliação');
});

it('envia o item novo pra avaliacao a partir da busca geral, com observacao opcional', function () {
    Livewire::test(BuscaGeral::class)
        ->set('busca', 'fogueira')
        ->call('abrirEnvioAvaliacao', $this->itemNovo->id)
        ->set("observacoesAvaliacao.{$this->itemNovo->id}", 'Fiz no acampamento.')
        ->call('solicitarNovo', $this->itemNovo->id)
        ->assertSet('enviandoAvaliacaoItemId', null);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->observacao_jovem)->toBe('Fiz no acampamento.');
});
