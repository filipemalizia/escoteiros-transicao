<?php

use App\Filament\Resources\EspecialidadeDistintivos\Pages\ListEspecialidadeDistintivos;
use App\Models\EspecialidadeDistintivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('filtra pra mostrar so especialidades/insignias sem imagem_nivel_1 cadastrada', function () {
    $comImagem = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);
    $comImagem->addMedia(pixelPngFile())->toMediaCollection('imagem_nivel_1');

    $semImagem = EspecialidadeDistintivo::create(['nome' => 'Primeiros Socorros', 'tipo' => 'Especialidade']);

    Livewire::test(ListEspecialidadeDistintivos::class)
        ->assertCanSeeTableRecords([$comImagem, $semImagem]);

    Livewire::test(ListEspecialidadeDistintivos::class)
        ->filterTable('sem_imagem')
        ->assertCanSeeTableRecords([$semImagem])
        ->assertCanNotSeeTableRecords([$comImagem]);
});
