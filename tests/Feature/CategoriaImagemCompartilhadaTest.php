<?php

use App\Filament\Resources\EixoNovos\Pages\CreateEixoNovo;
use App\Models\BlocoNovo;
use App\Models\CategoriaImagem;
use App\Models\EixoNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('compartilha a mesma imagem entre eixos do mesmo nome em ramos diferentes', function () {
    $lobinho = Ramo::create(['nome' => 'Lobinho']);
    $escoteiro = Ramo::create(['nome' => 'Escoteiro']);

    $categoria = CategoriaImagem::create(['tipo' => 'eixo', 'chave' => 'Meio Ambiente']);
    $categoria->addMedia(pixelPngFile())->toMediaCollection('imagem');

    $eixoLobinho = EixoNovo::create(['ramo_id' => $lobinho->id, 'nome' => 'Meio Ambiente', 'categoria_imagem_id' => $categoria->id]);
    $eixoEscoteiro = EixoNovo::create(['ramo_id' => $escoteiro->id, 'nome' => 'Meio Ambiente', 'categoria_imagem_id' => $categoria->id]);

    expect($eixoLobinho->categoriaImagem->getFirstMediaUrl('imagem'))
        ->not->toBeEmpty()
        ->toBe($eixoEscoteiro->categoriaImagem->getFirstMediaUrl('imagem'));
});

it('a mesma logica de compartilhamento vale pra blocos', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    $categoria = CategoriaImagem::create(['tipo' => 'bloco', 'chave' => 'Mudanças Climáticas']);
    $categoria->addMedia(pixelPngFile())->toMediaCollection('imagem');

    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Mudanças Climáticas', 'categoria_imagem_id' => $categoria->id]);

    expect($bloco->categoriaImagem->getFirstMediaUrl('imagem'))->not->toBeEmpty();
});

it('nao mistura categorias de imagem de tipos diferentes com o mesmo nome', function () {
    CategoriaImagem::create(['tipo' => 'eixo', 'chave' => 'Meio Ambiente']);
    CategoriaImagem::create(['tipo' => 'bloco', 'chave' => 'Meio Ambiente']);

    expect(CategoriaImagem::count())->toBe(2);
});

it('admin consegue criar um eixo escolhendo uma categoria de imagem existente pelo Filament', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $ramo = Ramo::create(['nome' => 'Pioneiro']);
    $categoria = CategoriaImagem::create(['tipo' => 'eixo', 'chave' => 'Meio Ambiente']);

    Livewire::test(CreateEixoNovo::class)
        ->fillForm([
            'ramo_id' => $ramo->id,
            'nome' => 'Meio Ambiente',
            'categoria_imagem_id' => $categoria->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $eixo = EixoNovo::where('ramo_id', $ramo->id)->first();

    expect($eixo->categoria_imagem_id)->toBe($categoria->id);
});

it('admin consegue criar uma nova categoria de imagem com upload direto de dentro do modal', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Ramo::create(['nome' => 'Pioneiro']);

    Livewire::test(CreateEixoNovo::class)
        ->mountFormComponentAction('categoria_imagem_id', 'createOption')
        ->setFormComponentActionData([
            'tipo' => 'eixo',
            'chave' => 'Paz e Desenvolvimento',
            'imagem' => UploadedFile::fake()->image('eixo.png'),
        ])
        ->callMountedFormComponentAction();

    $categoria = CategoriaImagem::where('chave', 'Paz e Desenvolvimento')->first();

    expect($categoria)->not->toBeNull()
        ->and($categoria->tipo)->toBe('eixo')
        ->and($categoria->getFirstMediaUrl('imagem'))->not->toBeEmpty();

    // Regressão: o upload direto (drag-and-drop) caía no disco 'local' do
    // Filament (config('filament.default_filesystem_disk'), não servido
    // publicamente) em vez do 'public' usado pelo resto do Media Library,
    // porque o campo não fixava ->disk('public') explicitamente.
    expect($categoria->getFirstMedia('imagem')->disk)->toBe('public');
});
