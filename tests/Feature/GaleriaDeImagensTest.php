<?php

use App\Filament\Pages\GaleriaDeImagens;
use App\Models\BlocoNovo;
use App\Models\CategoriaImagem;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('renderiza a galeria com eixos, blocos e especialidades cadastrados', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco A']);

    $categoriaEixo = CategoriaImagem::create(['tipo' => 'eixo', 'chave' => 'Eixo Corporal']);
    $categoriaEixo->addMedia(pixelPngFile())->toMediaCollection('imagem');
    $eixo->update(['categoria_imagem_id' => $categoriaEixo->id]);

    EspecialidadeDistintivo::create([
        'nome' => 'Nós e Amarras',
        'tipo' => 'Especialidade',
    ]);

    Livewire::test(GaleriaDeImagens::class)
        ->assertOk()
        ->assertSee('Eixo Corporal')
        ->assertSee('Nós e Amarras');
});

it('bloqueia o acesso de quem nao e admin', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    expect(GaleriaDeImagens::canAccess())->toBeFalse();
});
