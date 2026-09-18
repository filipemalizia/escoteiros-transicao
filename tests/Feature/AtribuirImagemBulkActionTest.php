<?php

use App\Filament\Resources\BlocoNovos\Pages\ListBlocoNovos;
use App\Filament\Resources\EixoNovos\Pages\ListEixoNovos;
use App\Models\BlocoNovo;
use App\Models\CategoriaImagem;
use App\Models\EixoNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('atribui uma categoria de imagem existente a varios eixos de uma vez', function () {
    $lobinho = Ramo::create(['nome' => 'Lobinho']);
    $escoteiro = Ramo::create(['nome' => 'Escoteiro']);

    $eixoLobinho = EixoNovo::create(['ramo_id' => $lobinho->id, 'nome' => 'Meio Ambiente']);
    $eixoEscoteiro = EixoNovo::create(['ramo_id' => $escoteiro->id, 'nome' => 'Meio Ambiente']);

    $categoria = CategoriaImagem::create(['tipo' => 'eixo', 'chave' => 'Meio Ambiente']);
    $categoria->addMedia(pixelPngFile())->toMediaCollection('imagem');

    Livewire::test(ListEixoNovos::class)
        ->callTableBulkAction('atribuirImagem', [$eixoLobinho, $eixoEscoteiro], data: [
            'categoria_imagem_id' => $categoria->id,
        ]);

    expect($eixoLobinho->fresh()->categoria_imagem_id)->toBe($categoria->id)
        ->and($eixoEscoteiro->fresh()->categoria_imagem_id)->toBe($categoria->id);
});

it('atribui uma categoria de imagem existente a varios blocos de uma vez', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco1 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco A']);
    $bloco2 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco B']);

    $categoria = CategoriaImagem::create(['tipo' => 'bloco', 'chave' => 'Bloco Compartilhado']);
    $categoria->addMedia(pixelPngFile())->toMediaCollection('imagem');

    Livewire::test(ListBlocoNovos::class)
        ->callTableBulkAction('atribuirImagem', [$bloco1, $bloco2], data: [
            'categoria_imagem_id' => $categoria->id,
        ]);

    expect($bloco1->fresh()->categoria_imagem_id)->toBe($categoria->id)
        ->and($bloco2->fresh()->categoria_imagem_id)->toBe($categoria->id);
});

it('cria uma categoria de imagem nova com upload e atribui a varios blocos de uma vez', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco1 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco A']);
    $bloco2 = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco B']);

    Livewire::test(ListBlocoNovos::class)
        ->callTableBulkAction('atribuirImagem', [$bloco1, $bloco2], data: [
            'nova_chave' => 'Bloco Compartilhado',
            'nova_imagem' => UploadedFile::fake()->image('bloco.png', 50, 50),
        ])
        ->assertHasNoTableBulkActionErrors();

    $categoria = CategoriaImagem::where('tipo', 'bloco')->where('chave', 'Bloco Compartilhado')->first();

    expect($categoria)->not->toBeNull()
        ->and($categoria->getFirstMedia('imagem')?->disk)->toBe('public')
        ->and($bloco1->fresh()->categoria_imagem_id)->toBe($categoria->id)
        ->and($bloco2->fresh()->categoria_imagem_id)->toBe($categoria->id);
});
