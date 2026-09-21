<?php

use App\Livewire\Portal\Revisao;
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

it('lista itens de bloco, personalizados e de especialidade aguardando revisao, ordenados por data de envio', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);

    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Fazer trilha', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => '2026-01-10 10:00:00',
        'observacao_jovem' => 'Fiz no acampamento.',
    ]);

    $itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $bloco->id, 'descricao' => 'Tarefa extra']);
    ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $itemPersonalizado->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => '2026-01-20 10:00:00',
    ]);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $itemEspecialidade = $grupo->itens()->create(['texto' => 'Montar barraca']);
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $itemEspecialidade->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => '2026-01-15 10:00:00',
    ]);

    Livewire::test(Revisao::class)
        ->assertSeeInOrder(['Tarefa extra', 'Montar barraca', 'Fazer trilha'])
        ->assertSee('Bloco 1 (Eixo Corporal)')
        ->assertSee('Acampamento')
        ->assertSee('Fiz no acampamento.')
        ->assertSee('Enviado em 10/01/2026');
});

it('mostra mensagem quando nao ha nada aguardando revisao', function () {
    Livewire::test(Revisao::class)
        ->assertSee('Nada aguardando revisão no momento.');
});

it('some da lista quando o item deixa de estar solicitado (aprovado ou recusado pelo chefe)', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Fazer trilha', 'tipo_acao' => 'Obrigatória']);

    $progresso = ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    Livewire::test(Revisao::class)->assertSee('Fazer trilha');

    $progresso->update(['concluido' => true, 'solicitado_pelo_jovem' => false]);

    Livewire::test(Revisao::class)
        ->assertDontSee('Fazer trilha')
        ->assertSee('Nada aguardando revisão no momento.');
});

it('conta itens de bloco, personalizados e de especialidade aguardando revisao pro badge da aba', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Fazer trilha', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    $itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $bloco->id, 'descricao' => 'Tarefa extra']);
    ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $itemPersonalizado->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    $contagem = app(StatusProgressaoService::class)->contagemAguardandoRevisao($this->jovem);

    expect($contagem)->toBe(2);

    // A tela inicial (qualquer página do portal) mostra o badge com esse
    // número na aba Revisão.
    $this->get(route('portal.progresso'))->assertOk();
});
