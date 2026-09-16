<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\Equipe;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoPersonalizado;
use App\Models\Ramo;
use App\Models\User;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->equipe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe A']);
    $this->outraEquipe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe B']);

    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
        'equipe_id' => $this->equipe->id,
    ]);

    $this->outroJovemMesmaEquipe = Jovem::create([
        'nome' => 'Outro Jovem Mesma Equipe',
        'data_nascimento' => '2011-01-01',
        'ramo_atual_id' => $this->ramo->id,
        'equipe_id' => $this->equipe->id,
    ]);

    $this->jovemDeOutraEquipe = Jovem::create([
        'nome' => 'Jovem de Outra Equipe',
        'data_nascimento' => '2011-01-01',
        'ramo_atual_id' => $this->ramo->id,
        'equipe_id' => $this->outraEquipe->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obrigatória', 'tipo_acao' => 'Obrigatória']);

    $this->lider = User::factory()->create();
    $this->lider->equipes()->attach($this->equipe);
});

it('lider cria um item personalizado so pro jovem que esta vendo por padrao', function () {
    $this->actingAs($this->lider);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirFormularioItemPersonalizado', $this->bloco->id)
        ->set('novoItemPersonalizadoDescricao', 'Desafio especial')
        ->call('criarItemPersonalizado');

    $item = ItemPersonalizado::where('descricao', 'Desafio especial')->first();

    expect($item)->not->toBeNull()
        ->and($item->criado_por_id)->toBe($this->lider->id)
        ->and($item->jovens->pluck('id')->all())->toBe([$this->jovem->id]);
});

it('filtra jovens que o lider nao tem acesso ao selecionar outros jovens', function () {
    $this->actingAs($this->lider);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirFormularioItemPersonalizado', $this->bloco->id)
        ->set('novoItemPersonalizadoDescricao', 'Desafio em grupo')
        ->set('novoItemPersonalizadoOutrosJovensIds', [$this->outroJovemMesmaEquipe->id, $this->jovemDeOutraEquipe->id])
        ->call('criarItemPersonalizado');

    $item = ItemPersonalizado::where('descricao', 'Desafio em grupo')->first();
    $idsVinculados = $item->jovens->pluck('id')->sort()->values()->all();

    expect($idsVinculados)->toBe(collect([$this->jovem->id, $this->outroJovemMesmaEquipe->id])->sort()->values()->all())
        ->and($idsVinculados)->not->toContain($this->jovemDeOutraEquipe->id);
});

it('item personalizado concluido conta na cota de acoes variaveis do bloco', function () {
    $item = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Extra', 'criado_por_id' => $this->lider->id]);
    $item->jovens()->attach($this->jovem->id);

    $status = app(StatusProgressaoService::class)->statusBloco($this->jovem, $this->bloco->fresh());
    expect($status['variaveis_concluidas'])->toBe(0);

    ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => now(),
    ]);

    app(StatusProgressaoService::class)->limparCache();
    $status = app(StatusProgressaoService::class)->statusBloco($this->jovem, $this->bloco->fresh());

    expect($status['variaveis_concluidas'])->toBe(1)
        ->and($status['variaveis_concluidas_via_personalizado'])->toBe(1);
});

it('item personalizado pendente aparece na lista de pendencias do bloco', function () {
    $item = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Desafio pendente', 'criado_por_id' => $this->lider->id]);
    $item->jovens()->attach($this->jovem->id);

    $pendencias = app(StatusProgressaoService::class)->pendenciasNovo($this->jovem);

    expect($pendencias)->toHaveCount(1);

    $variaveisPendentes = $pendencias[0]['variaveis_pendentes'];

    expect(collect($variaveisPendentes)->contains(fn ($i) => $i instanceof ItemPersonalizado && $i->id === $item->id))->toBeTrue();
});

it('item personalizado concluido nao aparece mais na lista de pendencias', function () {
    $item = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Desafio concluido', 'criado_por_id' => $this->lider->id]);
    $item->jovens()->attach($this->jovem->id);

    ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => now(),
    ]);

    $pendencias = app(StatusProgressaoService::class)->pendenciasNovo($this->jovem);

    // Bloco fica Concluído (1 obrigatória feita não, mas variável satisfeita
    // via personalizado) — como a obrigatória segue pendente, o bloco
    // continua aparecendo, só sem o item personalizado na lista.
    expect($pendencias)->toHaveCount(1);

    $variaveisPendentes = $pendencias[0]['variaveis_pendentes'];

    expect(collect($variaveisPendentes)->contains(fn ($i) => $i instanceof ItemPersonalizado && $i->id === $item->id))->toBeFalse();
});

it('lider confirma uma solicitacao de item personalizado', function () {
    $item = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Extra', 'criado_por_id' => $this->lider->id]);
    $item->jovens()->attach($this->jovem->id);

    ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    $this->actingAs($this->lider);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('confirmarItemPersonalizado', $item->id);

    $progresso = ProgressoPersonalizado::where('item_personalizado_id', $item->id)->first();

    expect($progresso->concluido)->toBeTrue()
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse();
});

it('lider sem acesso a nenhum jovem vinculado nao pode gerenciar o item personalizado', function () {
    $item = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Extra', 'criado_por_id' => $this->lider->id]);
    $item->jovens()->attach($this->jovemDeOutraEquipe->id);

    $liderSemAcesso = User::factory()->create();
    $liderSemAcesso->equipes()->attach($this->equipe);

    expect($liderSemAcesso->can('delete', $item))->toBeFalse();
});

it('lider com acesso a pelo menos um jovem vinculado pode excluir o item mesmo sem ter criado', function () {
    $criador = User::factory()->create();
    $criador->equipes()->attach($this->equipe);

    $item = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Extra', 'criado_por_id' => $criador->id]);
    $item->jovens()->attach($this->jovem->id);

    expect($this->lider->can('delete', $item))->toBeTrue();

    $this->actingAs($this->lider);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('excluirItemPersonalizado', $item->id);

    expect(ItemPersonalizado::find($item->id))->toBeNull();
});
