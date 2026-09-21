<?php

namespace App\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Converte um arquivo de imagem local numa data URI base64 — usado só pelo
 * cartão de conquista compartilhável ({@see resources/js/app.js},
 * `desenharCartaoConquista`), que desenha a imagem num `<canvas>` no
 * browser e depois precisa exportá-lo (`canvas.toBlob()`).
 *
 * Um `<canvas>` só exporta se toda imagem nele desenhada for "origin-clean"
 * — carregar por URL (mesmo do próprio disco `public`) e depender de CORS
 * pra isso é frágil: quebra se o app for acessado por um host diferente do
 * configurado em `APP_URL` (`127.0.0.1` vs `localhost`, domínio do Herd,
 * proxy reverso, etc.), mesmo a imagem sendo servida pelo próprio app. Uma
 * data URI nunca é cross-origin pro `<canvas>`, então essa classe inteira
 * de problema simplesmente não existe.
 */
class ImagemDataUriService
{
    public function paraCaminho(?string $caminhoAbsoluto): ?string
    {
        if (! $caminhoAbsoluto || ! is_file($caminhoAbsoluto)) {
            return null;
        }

        $mime = mime_content_type($caminhoAbsoluto) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($caminhoAbsoluto));
    }

    public function paraMedia(?Media $media): ?string
    {
        return $media ? $this->paraCaminho($media->getPath()) : null;
    }
}
