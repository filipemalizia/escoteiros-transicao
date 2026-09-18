<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);

    $this->ramo = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2015-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Meio Ambiente']);

    $this->especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Acampamento',
        'tipo' => 'Especialidade',
        'estrutura' => 'itens_niveis',
        'minimo_nivel_1' => 1,
        'minimo_nivel_2' => 2,
    ]);
    $this->especialidade->eixosNovos()->attach($eixo->id);

    $grupo = $this->especialidade->grupos()->create(['chave' => 'itens']);
    $this->item = $grupo->itens()->create(['texto' => 'Montar barraca']);

    $this->progresso = ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);
});

it('renderiza a pagina de progresso com a secao de especialidades', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertOk()
        ->assertSee('Especialidades')
        ->assertSee('Acampamento')
        ->assertSee('Montar barraca');
});

it('mostra a observacao do jovem pro chefe quando existir', function () {
    $this->progresso->update(['observacao_jovem' => 'Fiz o acampamento no fim de semana passado.']);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSee('Fiz o acampamento no fim de semana passado.');
});

it('abre o modal de avaliacao ao clicar no botao, e fecha ao cancelar', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSet('avaliandoItemId', null)
        ->call('abrirAvaliacao', 'especialidade', $this->item->id)
        ->assertSet('avaliandoTipo', 'especialidade')
        ->assertSet('avaliandoItemId', $this->item->id)
        ->assertSee('Aprovar')
        ->assertSee('Recusar')
        ->call('fecharAvaliacao')
        ->assertSet('avaliandoItemId', null);
});

it('fecha o modal automaticamente ao aprovar ou recusar', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirAvaliacao', 'especialidade', $this->item->id)
        ->call('confirmarEspecialidade', $this->item->id)
        ->assertSet('avaliandoItemId', null);

    $this->progresso->update(['solicitado_pelo_jovem' => true, 'solicitado_em' => now(), 'concluido' => false]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirAvaliacao', 'especialidade', $this->item->id)
        ->call('rejeitarEspecialidade', $this->item->id)
        ->assertSet('avaliandoItemId', null);
});

it('aprova ou recusa pelos botoes genericos do modal, de acordo com o tipo aberto', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirAvaliacao', 'especialidade', $this->item->id)
        ->call('confirmarAvaliacaoAtual')
        ->assertSet('avaliandoItemId', null);

    $progresso = $this->progresso->fresh();

    expect($progresso->concluido)->toBeTrue();

    $this->progresso->update(['solicitado_pelo_jovem' => true, 'solicitado_em' => now(), 'concluido' => false]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirAvaliacao', 'especialidade', $this->item->id)
        ->call('rejeitarAvaliacaoAtual')
        ->assertSet('avaliandoItemId', null);

    expect($this->progresso->fresh()->solicitado_pelo_jovem)->toBeFalse();
});

it('marca um item de especialidade como concluido via toggle', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleEspecialidade', $this->item->id);

    $progresso = $this->progresso->fresh();

    expect($progresso->concluido)->toBeTrue()
        ->and($progresso->registrado_por_id)->toBe($this->admin->id)
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse()
        ->and($progresso->solicitado_em)->toBeNull();
});

it('confirma uma solicitacao de especialidade, marcando concluido e limpando a solicitacao', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('confirmarEspecialidade', $this->item->id);

    $progresso = $this->progresso->fresh();

    expect($progresso->concluido)->toBeTrue()
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse();
});

it('rejeita uma solicitacao de especialidade sem marcar concluido', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('rejeitarEspecialidade', $this->item->id);

    $progresso = $this->progresso->fresh();

    expect($progresso->concluido)->toBeFalse()
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse();
});
