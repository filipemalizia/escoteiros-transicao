<?php

use App\Filament\Pages\EquivalenciaEmLote;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\Equivalencia;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->ramo = Ramo::create(['nome' => 'Sênior']);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'FÍSICO']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $this->antigos = collect(range(1, 3))->map(
        fn ($i) => ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => "FIS-00{$i}", 'descricao' => "Item {$i}"])
    );

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco', 'quantidade_minima_variaveis' => 1]);
    $this->novos = collect(range(1, 3))->map(
        fn ($i) => ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "HPV-00{$i}", 'descricao' => "Item {$i}", 'tipo_acao' => 'Variável'])
    );
});

it('cria uma equivalencia N-1 compartilhando o mesmo item_novo_id', function () {
    Livewire::test(EquivalenciaEmLote::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'tipo_equivalencia' => 'N-1',
            'itens_antigo_ids' => $this->antigos->pluck('id')->all(),
            'item_novo_id' => $this->novos[0]->id,
        ])
        ->call('criar');

    expect(Equivalencia::count())->toBe(3)
        ->and(Equivalencia::where('item_novo_id', $this->novos[0]->id)->count())->toBe(3);
});

it('cria uma equivalencia 1-N compartilhando o mesmo item_antigo_id', function () {
    Livewire::test(EquivalenciaEmLote::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'tipo_equivalencia' => '1-N',
            'item_antigo_id' => $this->antigos[0]->id,
            'itens_novo_ids' => $this->novos->pluck('id')->all(),
        ])
        ->call('criar');

    expect(Equivalencia::count())->toBe(3)
        ->and(Equivalencia::where('item_antigo_id', $this->antigos[0]->id)->count())->toBe(3);
});

it('nao duplica equivalencias ja existentes ao rodar o mesmo lote duas vezes', function () {
    $dados = [
        'ramo_id' => $this->ramo->id,
        'tipo_equivalencia' => 'N-1',
        'itens_antigo_ids' => $this->antigos->pluck('id')->all(),
        'item_novo_id' => $this->novos[0]->id,
    ];

    Livewire::test(EquivalenciaEmLote::class)->fillForm($dados)->call('criar');
    Livewire::test(EquivalenciaEmLote::class)->fillForm($dados)->call('criar');

    expect(Equivalencia::count())->toBe(3);
});
