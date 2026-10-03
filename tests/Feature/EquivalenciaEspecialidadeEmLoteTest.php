<?php

use App\Filament\Pages\EquivalenciaEspecialidadeEmLote;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EquivalenciaEspecialidade;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Aprendizagem Contínua', 'quantidade_minima_variaveis' => 3]);
    $this->itemNovo = ItemNovo::create([
        'bloco_id' => $bloco->id,
        'codigo' => 'HPV-001',
        'descricao' => 'Item substitutivo',
        'tipo_acao' => 'Substitutiva',
    ]);

    $this->especialidades = collect(range(1, 3))->map(
        fn ($i) => EspecialidadeDistintivo::create(['nome' => "Especialidade {$i}", 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis'])
    );
});

it('cria vinculos de equivalencia de especialidade para varias especialidades de uma vez', function () {
    Livewire::test(EquivalenciaEspecialidadeEmLote::class)
        ->fillForm([
            'item_novo_id' => $this->itemNovo->id,
            'especialidade_distintivo_ids' => $this->especialidades->pluck('id')->all(),
        ])
        ->call('criar');

    expect(EquivalenciaEspecialidade::count())->toBe(3)
        ->and(EquivalenciaEspecialidade::where('item_novo_id', $this->itemNovo->id)->count())->toBe(3);
});

it('aplica o mesmo nivel minimo a todos os vinculos criados no lote', function () {
    Livewire::test(EquivalenciaEspecialidadeEmLote::class)
        ->fillForm([
            'item_novo_id' => $this->itemNovo->id,
            'especialidade_distintivo_ids' => $this->especialidades->pluck('id')->all(),
            'nivel_minimo' => 2,
        ])
        ->call('criar');

    expect(EquivalenciaEspecialidade::where('nivel_minimo', 2)->count())->toBe(3);
});

it('nao duplica vinculos ja existentes ao rodar o mesmo lote duas vezes', function () {
    $dados = [
        'item_novo_id' => $this->itemNovo->id,
        'especialidade_distintivo_ids' => $this->especialidades->pluck('id')->all(),
    ];

    Livewire::test(EquivalenciaEspecialidadeEmLote::class)->fillForm($dados)->call('criar');
    Livewire::test(EquivalenciaEspecialidadeEmLote::class)->fillForm($dados)->call('criar');

    expect(EquivalenciaEspecialidade::count())->toBe(3);
});
