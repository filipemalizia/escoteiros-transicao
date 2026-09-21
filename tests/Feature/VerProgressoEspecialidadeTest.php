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

it('abre por padrao na aba do programa novo, sem mostrar especialidades/insignias', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertOk()
        ->assertSet('abaAtiva', 'novo')
        ->assertDontSee('Acampamento')
        ->assertDontSee('Montar barraca');
});

it('mostra a secao de especialidades na propria aba', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'especialidades')
        ->assertSee('Acampamento')
        ->assertSee('Montar barraca');
});

it('mostra insignias na propria aba, separada das especialidades', function () {
    $insignia = EspecialidadeDistintivo::create([
        'nome' => 'Mensageiros da Paz',
        'tipo' => 'Insígnia',
        'estrutura' => 'atividades_temas',
    ]);
    $insignia->eixosNovos()->attach($this->especialidade->eixosNovos->first()->id);
    $grupo = $insignia->grupos()->create(['chave' => 'conhecer']);
    $grupo->itens()->create(['texto' => 'Conhecer a paz']);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'especialidades')
        ->assertSee('Acampamento')
        ->assertDontSee('Mensageiros da Paz');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'insignias')
        ->assertSee('Mensageiros da Paz')
        ->assertDontSee('Acampamento');
});

it('cada secao tem sua propria busca, independente uma da outra', function () {
    $outraEspecialidade = EspecialidadeDistintivo::create([
        'nome' => 'Primeiros Socorros',
        'tipo' => 'Especialidade',
        'estrutura' => 'atividades_temas',
    ]);
    $outraEspecialidade->eixosNovos()->attach($this->especialidade->eixosNovos->first()->id);

    // busca na aba de Especialidades nao afeta a de Insignias (buscas independentes)
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'especialidades')
        ->set('buscaEspecialidades', 'acampa')
        ->assertSee('Acampamento')
        ->assertDontSee('Primeiros Socorros');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'especialidades')
        ->set('buscaEspecialidades', 'nao existe')
        ->assertDontSee('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('conta avaliacoes pendentes separadamente por especialidade e insignia', function () {
    $insignia = EspecialidadeDistintivo::create([
        'nome' => 'Mensageiros da Paz',
        'tipo' => 'Insígnia',
        'estrutura' => 'atividades_temas',
    ]);
    $insignia->eixosNovos()->attach($this->especialidade->eixosNovos->first()->id);
    $grupo = $insignia->grupos()->create(['chave' => 'conhecer']);
    $itemInsignia = $grupo->itens()->create(['texto' => 'Conhecer a paz']);

    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $itemInsignia->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    $component = Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()]);

    expect($component->instance()->getAvaliacoesPendentesPorTipo('Especialidade'))->toBe(1)
        ->and($component->instance()->getAvaliacoesPendentesPorTipo('Insígnia'))->toBe(1);
});

it('mostra a observacao do jovem pro chefe quando existir', function () {
    $this->progresso->update(['observacao_jovem' => 'Fiz o acampamento no fim de semana passado.']);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'especialidades')
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

it('dispara o popup de conquista ao completar a especialidade via toggle, com o nivel atingido', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleEspecialidade', $this->item->id)
        ->assertDispatched(
            'abrir-cartao-conquista',
            tipo: 'especialidade',
            titulo: 'Acampamento',
            jovemNome: $this->jovem->nomeExibicao(),
            nivel: 1,
        );
});

it('dispara o popup de conquista sem nivel quando a especialidade nao usa estrutura de niveis', function () {
    $insignia = EspecialidadeDistintivo::create([
        'nome' => 'Mensageiros da Paz',
        'tipo' => 'Insígnia',
        'estrutura' => 'atividades_temas',
    ]);
    $insignia->eixosNovos()->attach($this->especialidade->eixosNovos->first()->id);
    $grupo = $insignia->grupos()->create(['chave' => 'conhecer']);
    $item = $grupo->itens()->create(['texto' => 'Conhecer a paz']);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleEspecialidade', $item->id)
        ->assertDispatched('abrir-cartao-conquista', tipo: 'insignia', titulo: 'Mensageiros da Paz', nivel: null);
});

it('dispara o popup de conquista ao confirmar a solicitacao da especialidade', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('confirmarEspecialidade', $this->item->id)
        ->assertDispatched('abrir-cartao-conquista', tipo: 'especialidade', titulo: 'Acampamento');
});

it('nao dispara o popup de conquista ao desmarcar uma especialidade ja concluida', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleEspecialidade', $this->item->id)
        ->assertDispatched('abrir-cartao-conquista');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleEspecialidade', $this->item->id)
        ->assertNotDispatched('abrir-cartao-conquista');
});

it('nao dispara o popup de conquista quando a especialidade ja estava concluida antes da acao', function () {
    $this->progresso->update(['concluido' => true, 'data_conclusao' => today()]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('confirmarEspecialidade', $this->item->id)
        ->assertNotDispatched('abrir-cartao-conquista');
});

it('dispara o popup de conquista com tipo insignia ao completar uma insignia', function () {
    $insignia = EspecialidadeDistintivo::create([
        'nome' => 'Mensageiros da Paz',
        'tipo' => 'Insígnia',
        'estrutura' => 'atividades_temas',
    ]);
    $insignia->eixosNovos()->attach($this->especialidade->eixosNovos->first()->id);
    $grupo = $insignia->grupos()->create(['chave' => 'conhecer']);
    $item = $grupo->itens()->create(['texto' => 'Conhecer a paz']);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleEspecialidade', $item->id)
        ->assertDispatched('abrir-cartao-conquista', tipo: 'insignia', titulo: 'Mensageiros da Paz');
});
