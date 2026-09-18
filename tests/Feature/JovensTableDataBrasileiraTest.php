<?php

use App\Filament\Resources\Jovens\Pages\ListJovens;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('mostra a data de nascimento no padrao brasileiro (d/m/Y) na listagem de jovens', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-03-05', 'ramo_atual_id' => $ramo->id]);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(ListJovens::class)
        ->assertSee('05/03/2010')
        ->assertDontSee('Mar 5, 2010');
});
