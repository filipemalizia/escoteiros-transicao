<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\CompetenciaAntiga;
use App\Models\ItemAntigo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
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
