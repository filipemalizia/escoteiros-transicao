<?php

use App\Services\ImagemDataUriService;

it('converte um arquivo local numa data uri base64', function () {
    $service = new ImagemDataUriService;

    expect($service->paraCaminho(pixelPngFile()))->toBe('data:image/png;base64,'.pixelPngBase64());
});

it('retorna null quando o caminho e nulo ou o arquivo nao existe', function () {
    $service = new ImagemDataUriService;

    expect($service->paraCaminho(null))->toBeNull()
        ->and($service->paraCaminho('/caminho/que/nao/existe.png'))->toBeNull();
});
