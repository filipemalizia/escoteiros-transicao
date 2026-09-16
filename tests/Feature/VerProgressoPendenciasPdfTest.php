<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\CompetenciaAntiga;
use App\Models\ItemAntigo;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item pendente']);
});

it('baixa o pdf de pendencias com os dois programas', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->callAction('baixarPendenciasPdfTodos')
        ->assertFileDownloaded('pendencias-jovem-de-teste.pdf');
});

it('baixa o pdf de pendencias so do programa novo', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->callAction('baixarPendenciasPdfNovo')
        ->assertFileDownloaded('pendencias-jovem-de-teste-programa-novo.pdf');
});

it('baixa o pdf de pendencias so do programa antigo', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->callAction('baixarPendenciasPdfAntigo')
        ->assertFileDownloaded('pendencias-jovem-de-teste-programa-antigo.pdf');
});
