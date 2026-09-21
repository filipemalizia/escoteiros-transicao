<?php

use App\Filament\Resources\EquivalenciaEspecialidades\Pages\CreateEquivalenciaEspecialidade;
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

it('admin cria uma equivalencia entre especialidade e item novo pelo Filament', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Habilidades para a Vida']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco']);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'HPV-001', 'descricao' => 'Item 1', 'tipo_acao' => 'Substitutiva']);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);

    Livewire::test(CreateEquivalenciaEspecialidade::class)
        ->fillForm([
            'especialidade_distintivo_id' => $especialidade->id,
            'item_novo_id' => $item->id,
            'observacao' => 'Quem tem a especialidade de Acampamento já cumpre este item.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $equivalencia = EquivalenciaEspecialidade::where('item_novo_id', $item->id)->first();

    expect($equivalencia)->not->toBeNull()
        ->and($equivalencia->especialidade_distintivo_id)->toBe($especialidade->id);
});
