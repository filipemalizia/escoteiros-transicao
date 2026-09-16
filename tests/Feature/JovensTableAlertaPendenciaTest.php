<?php

use App\Filament\Resources\Jovens\Pages\ListJovens;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\Equipe;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->equipe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe A']);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco']);
    $this->item = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'I-1', 'descricao' => 'Item', 'tipo_acao' => 'Obrigatória']);

    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('marca o alerta para jovem com item pendente de avaliacao', function () {
    $jovem = Jovem::create(['nome' => 'Com Pendência', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $this->equipe->id]);

    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $this->item->id, 'concluido' => false, 'solicitado_pelo_jovem' => true, 'solicitado_em' => now()]);

    $this->actingAs($this->admin);

    $registro = Livewire::test(ListJovens::class)
        ->assertCanSeeTableRecords([$jovem])
        ->instance()
        ->getTable()
        ->getRecords()
        ->firstWhere('id', $jovem->id);

    expect($registro->tem_pendencia_novo)->toBeTrue()
        ->and((bool) $registro->tem_pendencia_antigo)->toBeFalse()
        ->and((bool) $registro->tem_pendencia_personalizado)->toBeFalse();
});

it('nao marca o alerta para jovem sem pendencias', function () {
    $jovem = Jovem::create(['nome' => 'Sem Pendência', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $this->equipe->id]);

    ProgressoNovo::create(['jovem_id' => $jovem->id, 'item_novo_id' => $this->item->id, 'concluido' => true]);

    $this->actingAs($this->admin);

    $registro = Livewire::test(ListJovens::class)
        ->instance()
        ->getTable()
        ->getRecords()
        ->firstWhere('id', $jovem->id);

    expect((bool) $registro->tem_pendencia_novo)->toBeFalse();
});

it('marca o alerta quando a pendencia vem de um item personalizado', function () {
    $jovem = Jovem::create(['nome' => 'Pendência Personalizada', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $this->equipe->id]);

    $itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Desafio', 'criado_por_id' => $this->admin->id]);
    $itemPersonalizado->jovens()->attach($jovem->id);

    ProgressoPersonalizado::create([
        'jovem_id' => $jovem->id,
        'item_personalizado_id' => $itemPersonalizado->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => now(),
    ]);

    $this->actingAs($this->admin);

    $registro = Livewire::test(ListJovens::class)
        ->instance()
        ->getTable()
        ->getRecords()
        ->firstWhere('id', $jovem->id);

    expect($registro->tem_pendencia_personalizado)->toBeTrue();
});
