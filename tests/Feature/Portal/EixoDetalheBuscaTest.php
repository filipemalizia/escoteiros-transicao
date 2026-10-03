<?php

use App\Livewire\Portal\EixoDetalhe;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
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

    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->blocoA = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco A', 'quantidade_minima_variaveis' => 1]);
    $this->blocoB = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco B', 'quantidade_minima_variaveis' => 1]);

    $this->itemFogueira = ItemNovo::create(['bloco_id' => $this->blocoA->id, 'codigo' => 'A-001', 'descricao' => 'Acender uma fogueira com segurança', 'tipo_acao' => 'Obrigatória']);
    $this->itemBarraca = ItemNovo::create(['bloco_id' => $this->blocoB->id, 'codigo' => 'B-001', 'descricao' => 'Montar a barraca sozinho', 'tipo_acao' => 'Obrigatória']);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('filtra os itens visiveis pelo codigo ou descricao', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])
        ->set('busca', 'fogueira')
        ->assertSeeText('Acender uma fogueira com segurança')
        ->assertDontSee('Montar a barraca sozinho');
});

it('oculta o bloco inteiro quando nenhum item dele bate com a busca', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])
        ->set('busca', 'fogueira')
        ->assertSee('id="bloco-'.$this->blocoA->id.'"', false)
        ->assertDontSee('id="bloco-'.$this->blocoB->id.'"', false);
});

it('destaca o trecho buscado', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])
        ->set('busca', 'fogueira')
        ->assertSee('<mark', false);
});

it('filtra tambem os itens personalizados', function () {
    $itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $this->blocoB->id, 'descricao' => 'Desafio de fogueira noturna']);
    $itemPersonalizado->jovens()->attach($this->jovem->id);

    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])
        ->set('busca', 'fogueira')
        ->assertSeeText('Desafio de fogueira noturna');
});

it('abre o bloco certo com o acordeao ja expandido quando vem com ?bloco= na url', function () {
    $conteudo = $this->get(route('portal.eixos.show', $this->eixo).'?bloco='.$this->blocoA->id)
        ->assertOk()
        ->getContent();

    expect(substr_count($conteudo, 'x-data="{ open: true }"'))->toBe(1)
        ->and(substr_count($conteudo, 'x-data="{ open: false }"'))->toBe(1);
});

it('sem ?bloco= todos os acordeoes vem fechados', function () {
    $conteudo = $this->get(route('portal.eixos.show', $this->eixo))
        ->assertOk()
        ->getContent();

    expect(substr_count($conteudo, 'x-data="{ open: true }"'))->toBe(0)
        ->and(substr_count($conteudo, 'x-data="{ open: false }"'))->toBe(2);
});

it('sem busca mostra todos os blocos e itens normalmente', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])
        ->assertSee('id="bloco-'.$this->blocoA->id.'"', false)
        ->assertSee('id="bloco-'.$this->blocoB->id.'"', false)
        ->assertSeeText('Acender uma fogueira com segurança')
        ->assertSeeText('Montar a barraca sozinho');
});
