<?php

use App\Filament\Resources\Jovens\Pages\EditJovem;
use App\Models\Jovem;
use App\Models\ProgressoAcolhida;
use App\Models\Ramo;
use App\Models\User;
use App\Services\AcolhidaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Escoteiro']);
    $this->jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2013-01-01', 'ramo_atual_id' => $this->ramo->id]);
    $this->service = new AcolhidaService;
});

it('cada ramo tem a sua lista de itens da acolhida', function () {
    expect($this->service->itensDoRamo('Lobinho'))->toHaveCount(7)
        ->and($this->service->itensDoRamo('Escoteiro'))->toHaveCount(7)
        ->and($this->service->itensDoRamo('Sênior'))->toHaveCount(7)
        ->and($this->service->itensDoRamo('Pioneiro'))->toHaveCount(4)
        ->and($this->service->itensDoRamo('Outro'))->toBe([])
        ->and(array_keys($this->service->itensDoRamo('Pioneiro')))->toBe([1, 2, 3, 4]);
});

it('calcula quantos itens da acolhida o jovem concluiu e quando chega a 100%', function () {
    expect($this->service->resumo($this->jovem))->toBe(['total' => 7, 'concluidos' => 0, 'percentual' => 0, 'completo' => false]);

    foreach (range(1, 3) as $ordem) {
        ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => $ordem, 'concluido' => true]);
    }
    // Pedido do jovem ainda não aprovado não conta como concluído.
    ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => 4, 'solicitado_pelo_jovem' => true]);

    $resumo = $this->service->resumo($this->jovem);
    expect($resumo['concluidos'])->toBe(3)->and($resumo['percentual'])->toBe(43)->and($resumo['completo'])->toBeFalse()
        ->and($this->service->contagemAguardandoAvaliacao($this->jovem))->toBe(1);

    foreach (range(4, 7) as $ordem) {
        ProgressoAcolhida::updateOrCreate(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => $ordem], ['concluido' => true]);
    }

    expect($this->service->resumo($this->jovem)['percentual'])->toBe(100)
        ->and($this->service->resumo($this->jovem)['completo'])->toBeTrue();
});

it('o progresso da acolhida e separado por ramo', function () {
    ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => 1, 'concluido' => true]);

    $outroRamo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem->update(['ramo_atual_id' => $outroRamo->id]);

    expect($this->service->resumo($this->jovem->fresh())['concluidos'])->toBe(0);
});

it('a Promessa Escoteira vale para Escoteiro, Senior e Pioneiro, mas a acolhida recomeca em cada ramo', function () {
    expect($this->jovem->promessaRealizada())->toBeFalse();

    $this->jovem->update(['data_promessa' => '2026-05-10']);
    expect($this->jovem->fresh()->promessaRealizada())->toBeTrue()
        ->and($this->jovem->fresh()->promessa_ramo_id)->toBe($this->ramo->id);

    ProgressoAcolhida::create(['jovem_id' => $this->jovem->id, 'ramo_id' => $this->ramo->id, 'ordem' => 1, 'concluido' => true]);

    foreach (['Sênior', 'Pioneiro'] as $nomeRamo) {
        $this->jovem->update(['ramo_atual_id' => Ramo::create(['nome' => $nomeRamo])->id]);

        expect($this->jovem->fresh()->promessaRealizada())->toBeTrue()
            ->and($this->service->resumo($this->jovem->fresh())['concluidos'])->toBe(0);
    }
});

it('a Promessa do Lobinho vale so para o Lobinho', function () {
    $lobinho = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem->update(['ramo_atual_id' => $lobinho->id, 'data_promessa' => '2025-06-01']);

    expect($this->jovem->fresh()->promessaRealizada())->toBeTrue()
        ->and($this->jovem->fresh()->nomeDaPromessaDoRamo())->toBe('Lobinho');

    $this->jovem->update(['ramo_atual_id' => $this->ramo->id]);

    expect($this->jovem->fresh()->promessaRealizada())->toBeFalse()
        ->and($this->jovem->fresh()->nomeDaPromessaDoRamo())->toBe('Escoteira');

    $this->jovem->update(['data_promessa' => '2026-03-15']);

    expect($this->jovem->fresh()->promessaRealizada())->toBeTrue()
        ->and($this->jovem->fresh()->promessa_ramo_id)->toBe($this->ramo->id);

    $this->jovem->update(['data_promessa' => null]);

    expect($this->jovem->fresh()->promessaRealizada())->toBeFalse()
        ->and($this->jovem->fresh()->promessa_ramo_id)->toBeNull();
});

it('o chefe preenche a data da promessa na edicao do jovem', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(EditJovem::class, ['record' => $this->jovem->getKey()])
        ->fillForm(['data_promessa' => '2026-04-20'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->jovem->fresh()->data_promessa->toDateString())->toBe('2026-04-20')
        ->and($this->jovem->fresh()->promessaRealizada())->toBeTrue();
});
