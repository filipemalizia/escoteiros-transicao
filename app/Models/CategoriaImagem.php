<?php

namespace App\Models;

use App\Services\ImagemDataUriService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Imagem compartilhada entre linhas ramo-escopadas que representam o mesmo
 * conceito (ex.: o eixo "Meio Ambiente" existe uma vez por ramo, mas usa a
 * mesma imagem nas 4). "tipo" + "chave" (nome do eixo / título do bloco)
 * identificam essa imagem compartilhada.
 */
class CategoriaImagem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'categorias_imagem';

    protected $fillable = ['tipo', 'chave'];

    public function eixosNovos(): HasMany
    {
        return $this->hasMany(EixoNovo::class, 'categoria_imagem_id');
    }

    public function blocosNovos(): HasMany
    {
        return $this->hasMany(BlocoNovo::class, 'categoria_imagem_id');
    }

    /**
     * Imagem como data URI base64 em vez de URL — usado só pelo cartão de
     * conquista compartilhável (ver {@see ImagemDataUriService}).
     */
    public function dataUriImagem(): ?string
    {
        return app(ImagemDataUriService::class)->paraMedia($this->getFirstMedia('imagem'));
    }
}
