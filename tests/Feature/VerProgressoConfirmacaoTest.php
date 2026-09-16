<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Models\User;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $this->itemAntigo = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item antigo']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    $this->itemNovo = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);

    $this->progressoAntigo = ProgressoAntigo::create([
        'jovem_id' => $this->jovem->id,
        'item_antigo_id' => $this->itemAntigo->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    $this->progressoNovo = ProgressoNovo::create([
        'jovem_id' => $this->jovem->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);
});

it('confirma uma solicitacao antiga, marcando concluido e limpando a solicitacao', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('confirmarAntigo', $this->itemAntigo->id);

    $progresso = $this->progressoAntigo->fresh();

    expect($progresso->concluido)->toBeTrue()
        ->and($progresso->registrado_por_id)->toBe($this->admin->id)
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse()
        ->and($progresso->solicitado_em)->toBeNull();
});

it('confirma uma solicitacao nova, marcando concluido e limpando a solicitacao', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('confirmarNovo', $this->itemNovo->id);

    $progresso = $this->progressoNovo->fresh();

    expect($progresso->concluido)->toBeTrue()
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse();
});

it('rejeita uma solicitacao antiga sem marcar concluido', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('rejeitarAntigo', $this->itemAntigo->id);

    $progresso = $this->progressoAntigo->fresh();

    expect($progresso->concluido)->toBeFalse()
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse()
        ->and($progresso->solicitado_em)->toBeNull();
});

it('rejeita uma solicitacao nova sem marcar concluido', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('rejeitarNovo', $this->itemNovo->id);

    $progresso = $this->progressoNovo->fresh();

    expect($progresso->concluido)->toBeFalse()
        ->and($progresso->solicitado_pelo_jovem)->toBeFalse();
});

it('nao conta um item apenas solicitado como concluido no status da competencia', function () {
    $status = app(StatusProgressaoService::class)->statusCompetencia($this->jovem, $this->itemAntigo->competencia);

    expect($status['status'])->toBe('Pendente')
        ->and($status['itens_concluidos'])->toBe(0);
});
