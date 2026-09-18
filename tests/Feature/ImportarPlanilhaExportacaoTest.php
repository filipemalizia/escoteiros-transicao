<?php

use App\Filament\Pages\ImportarPlanilha;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
});

it('avisa que precisa selecionar um ramo antes de baixar a progressao antiga', function () {
    Livewire::test(ImportarPlanilha::class)
        ->callAction('baixarProgressaoAntiga')
        ->assertNotified('Selecione um Ramo antes de baixar.');
});

it('baixa a progressao antiga cadastrada do ramo selecionado', function () {
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Corrida']);
    ItemAntigo::create([
        'competencia_id' => $competencia->id,
        'codigo' => 'FIS-101',
        'descricao' => "Correr 1km\n\nObservação: usar cronômetro",
        'etapa' => null,
        'introdutorio' => true,
    ]);

    Livewire::test(ImportarPlanilha::class)
        ->set('data.ramo_id', $this->ramo->id)
        ->callAction('baixarProgressaoAntiga')
        ->assertFileDownloaded("progressao-antiga-{$this->ramo->nome}.csv");
});

it('baixa a progressao novo cadastrada do ramo selecionado', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'descricao' => 'Intencionalidade']);
    ItemNovo::create([
        'bloco_id' => $bloco->id,
        'codigo' => 'B1-001',
        'descricao' => 'Fazer uma trilha',
        'tipo_acao' => 'Obrigatória',
        'modalidade' => 'Básica',
    ]);

    Livewire::test(ImportarPlanilha::class)
        ->set('data.ramo_id', $this->ramo->id)
        ->callAction('baixarProgressaoNovo')
        ->assertFileDownloaded("progressao-novo-{$this->ramo->nome}.csv");
});
