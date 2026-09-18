<?php

use App\Livewire\Portal\Catalogo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Meio Ambiente']);

    $this->especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Acampamento',
        'tipo' => 'Especialidade',
        'estrutura' => 'itens_niveis',
    ]);
    $this->especialidade->eixosNovos()->attach($eixo->id);

    $grupo = $this->especialidade->grupos()->create(['chave' => 'itens']);
    $this->item = $grupo->itens()->create(['texto' => 'Montar barraca']);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);
});

it('marca um item de especialidade como solicitado sem concluir direto', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('solicitarEspecialidade', $this->item->id);

    $progresso = ProgressoEspecialidade::where('jovem_id', $this->jovem->id)
        ->where('especialidade_distintivo_item_id', $this->item->id)
        ->first();

    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->solicitado_em)->not->toBeNull()
        ->and($progresso->concluido)->toBeFalse();
});

it('salva a observacao opcional do jovem ao solicitar avaliacao', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set("observacoesAvaliacao.{$this->item->id}", 'Fiz o acampamento no fim de semana passado.')
        ->call('solicitarEspecialidade', $this->item->id);

    $progresso = ProgressoEspecialidade::where('jovem_id', $this->jovem->id)
        ->where('especialidade_distintivo_item_id', $this->item->id)
        ->first();

    expect($progresso->observacao_jovem)->toBe('Fiz o acampamento no fim de semana passado.');
});

it('nao exige observacao pra solicitar avaliacao', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('solicitarEspecialidade', $this->item->id);

    $progresso = ProgressoEspecialidade::where('jovem_id', $this->jovem->id)
        ->where('especialidade_distintivo_item_id', $this->item->id)
        ->first();

    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->observacao_jovem)->toBeNull();
});

it('nao faz nada ao solicitar um item de especialidade que ja esta concluido', function () {
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => true,
        'data_conclusao' => now(),
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('solicitarEspecialidade', $this->item->id);

    $progresso = ProgressoEspecialidade::where('jovem_id', $this->jovem->id)
        ->where('especialidade_distintivo_item_id', $this->item->id)
        ->first();

    expect($progresso->solicitado_pelo_jovem)->toBeFalse();
});

it('jovem nao consegue abrir uma especialidade de outro ramo no modal', function () {
    $outroRamo = Ramo::create(['nome' => 'Sênior']);
    $outroEixo = EixoNovo::create(['ramo_id' => $outroRamo->id, 'nome' => 'Meio Ambiente']);

    $especialidadeDeOutroRamo = EspecialidadeDistintivo::create([
        'nome' => 'Agricultura Sustentável',
        'tipo' => 'Especialidade',
        'estrutura' => 'atividades_temas',
    ]);
    $especialidadeDeOutroRamo->eixosNovos()->attach($outroEixo->id);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('abrirEspecialidade', $especialidadeDeOutroRamo->id)
        ->assertSet('especialidadeAbertaId', $especialidadeDeOutroRamo->id)
        ->assertDontSee('Agricultura Sustentável');
});

it('renderiza a lista com a especialidade e abre o modal com seus itens', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->assertOk()
        ->assertSee('Acampamento')
        ->assertDontSee('Montar barraca')
        ->call('abrirEspecialidade', $this->especialidade->id)
        ->assertSee('Montar barraca');
});

it('abre o modal de envio ao clicar em enviar para avaliacao, e fecha ao cancelar', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('abrirEspecialidade', $this->especialidade->id)
        ->assertSet('enviandoAvaliacaoItemId', null)
        ->call('abrirEnvioAvaliacao', $this->item->id)
        ->assertSet('enviandoAvaliacaoItemId', $this->item->id)
        ->assertSee('Enviar para avaliação')
        ->call('fecharEnvioAvaliacao')
        ->assertSet('enviandoAvaliacaoItemId', null);
});

it('fecha o modal automaticamente depois de confirmar o envio', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('abrirEspecialidade', $this->especialidade->id)
        ->call('abrirEnvioAvaliacao', $this->item->id)
        ->call('solicitarEspecialidade', $this->item->id)
        ->assertSet('enviandoAvaliacaoItemId', null);
});

it('fecha o modal da especialidade e o de envio juntos', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('abrirEspecialidade', $this->especialidade->id)
        ->call('abrirEnvioAvaliacao', $this->item->id)
        ->call('fecharEspecialidade')
        ->assertSet('especialidadeAbertaId', null)
        ->assertSet('enviandoAvaliacaoItemId', null);
});
