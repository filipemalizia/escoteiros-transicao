<?php

use App\Filament\Pages\EquivalenciaBlocoEmLote;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EquivalenciaBloco;
use App\Models\ItemAntigo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->ramo = Ramo::create(['nome' => 'Sênior']);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Intelectual']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Aprendizagem']);
    $this->itensAntigos = collect(range(1, 3))->map(
        fn ($i) => ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => "I{$i}", 'descricao' => "Item {$i}"])
    );

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Habilidades para a Vida']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Aprendizagem Contínua', 'quantidade_minima_variaveis' => 3]);
});

it('cria vinculos de equivalencia de bloco para varios itens antigos de uma vez', function () {
    Livewire::test(EquivalenciaBlocoEmLote::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'bloco_novo_id' => $this->bloco->id,
            'itens_antigo_ids' => $this->itensAntigos->pluck('id')->all(),
        ])
        ->call('criar');

    expect(EquivalenciaBloco::count())->toBe(3)
        ->and(EquivalenciaBloco::where('bloco_novo_id', $this->bloco->id)->count())->toBe(3);
});

it('nao duplica vinculos ja existentes ao rodar o mesmo lote duas vezes', function () {
    $dados = [
        'ramo_id' => $this->ramo->id,
        'bloco_novo_id' => $this->bloco->id,
        'itens_antigo_ids' => $this->itensAntigos->pluck('id')->all(),
    ];

    Livewire::test(EquivalenciaBlocoEmLote::class)->fillForm($dados)->call('criar');
    Livewire::test(EquivalenciaBlocoEmLote::class)->fillForm($dados)->call('criar');

    expect(EquivalenciaBloco::count())->toBe(3);
});
