<?php

use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

uses(RefreshDatabase::class);

// Extende o model real (mesma tabela `ramos`) só pra este teste: valida os
// mecanismos do Media Library (Fase 1) sem alterar nenhum model de domínio
// ainda — isso é escopo das próximas fases.
class RamoComMedia extends Ramo implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'ramos';
}

it('anexa uma imagem via upload direto (addMedia) e salva no disco public', function () {
    Storage::fake('public');

    $ramo = RamoComMedia::create(['nome' => 'Teste']);
    $media = $ramo->addMedia(pixelPngFile())->toMediaCollection('imagem');

    expect($media->collection_name)->toBe('imagem')
        ->and($media->disk)->toBe('public')
        ->and($ramo->getFirstMediaUrl('imagem'))->not->toBeEmpty();

    Storage::disk('public')->assertExists($media->id.'/'.$media->file_name);
});

it('anexa uma imagem baixada de uma URL (addMediaFromUrl)', function () {
    Storage::fake('public');
    Http::fake([
        'exemplo.test/*' => Http::response(base64_decode(pixelPngBase64()), 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);

    $ramo = RamoComMedia::create(['nome' => 'Teste']);
    $media = $ramo->addMediaFromUrl('https://exemplo.test/imagem.png')->toMediaCollection('imagem');

    expect($media->collection_name)->toBe('imagem')
        ->and($ramo->fresh()->getMedia('imagem'))->toHaveCount(1);

    Storage::disk('public')->assertExists($media->id.'/'.$media->file_name);
});
