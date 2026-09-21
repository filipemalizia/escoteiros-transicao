<?php

use App\Filament\Resources\Jovens\JovemResource;
use App\Filament\Resources\Jovens\Pages\ListJovens;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('clicar na linha do jovem na listagem abre a tela de progresso, nao a edicao', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $ramo->id,
    ]);

    $tabela = Livewire::test(ListJovens::class)->instance()->getTable();

    expect($tabela->getRecordUrl($jovem))
        ->toBe(JovemResource::getUrl('progresso', ['record' => $jovem]));
});
