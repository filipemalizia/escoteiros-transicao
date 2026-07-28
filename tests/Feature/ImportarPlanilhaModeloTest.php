<?php

use App\Filament\Pages\ImportarPlanilha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('baixa o modelo csv do sistema antigo com as colunas esperadas', function () {
    Livewire::test(ImportarPlanilha::class)
        ->callAction('baixarModeloAntigo')
        ->assertFileDownloaded('modelo-importacao-antigo.csv');
});

it('baixa o modelo csv do sistema novo com as colunas esperadas', function () {
    Livewire::test(ImportarPlanilha::class)
        ->callAction('baixarModeloNovo')
        ->assertFileDownloaded('modelo-importacao-novo.csv');
});
