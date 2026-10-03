<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $this->item = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item de teste']);

    $this->progresso = ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $this->item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);
});

it('limpa a solicitacao pendente quando o adulto marca o item direto pelo checkbox', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleAntigo', $this->item->id);

    $progresso = $this->progresso->fresh();

    expect($progresso->concluido)->toBeTrue()
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse()
        ->and($progresso->solicitado_em)->toBeNull();
});

it('desfaz a marcacao de quero fazer quando um item novo e concluido via toggle', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->jovem->ramo_atual_id, 'nome' => 'Eixo Teste']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);
    $progresso = ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $item->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])->call('toggleNovo', $item->id);

    expect($progresso->fresh()->concluido)->toBeTrue()
        ->and($progresso->fresh()->marcado_para_fazer)->toBeFalse()
        ->and($progresso->fresh()->marcado_para_fazer_em)->toBeNull();
});

it('continua mostrando a observacao do jovem (item novo) mesmo depois de marcado direto pelo checkbox', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->jovem->ramo_atual_id, 'nome' => 'Eixo Teste']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $item->id,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
        'observacao_jovem' => 'Fiz com a ajuda do meu pai.',
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $item->id)
        ->assertSee('Fiz com a ajuda do meu pai.');
});

it('desfaz a marcacao de quero fazer quando um item novo e confirmado', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->jovem->ramo_atual_id, 'nome' => 'Eixo Teste']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);
    $progresso = ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $item->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])->call('confirmarNovo', $item->id);

    expect($progresso->fresh()->marcado_para_fazer)->toBeFalse();
});

it('desfaz a marcacao de quero fazer quando uma especialidade e concluida via toggle', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'atividades_temas']);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $item = $grupo->itens()->create(['texto' => 'Montar barraca']);
    $progresso = ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $item->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])->call('toggleEspecialidade', $item->id);

    expect($progresso->fresh()->concluido)->toBeTrue()
        ->and($progresso->fresh()->marcado_para_fazer)->toBeFalse();
});

it('desfaz a marcacao de quero fazer quando um item personalizado e concluido via toggle', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->jovem->ramo_atual_id, 'nome' => 'Eixo Teste']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $bloco->id, 'descricao' => 'Desafio especial']);
    $itemPersonalizado->jovens()->attach($this->jovem->id);
    $progresso = ProgressoPersonalizado::create([
        'jovem_id' => $this->jovem->id,
        'item_personalizado_id' => $itemPersonalizado->id,
        'marcado_para_fazer' => true,
        'marcado_para_fazer_em' => now(),
    ]);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])->call('toggleItemPersonalizado', $itemPersonalizado->id);

    expect($progresso->fresh()->concluido)->toBeTrue()
        ->and($progresso->fresh()->marcado_para_fazer)->toBeFalse();
});
