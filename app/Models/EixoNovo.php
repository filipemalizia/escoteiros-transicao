<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EixoNovo extends Model
{
    protected $table = 'eixos_novos';

    protected $fillable = ['ramo_id', 'nome', 'categoria_imagem_id'];

    public function ramo(): BelongsTo
    {
        return $this->belongsTo(Ramo::class);
    }

    public function categoriaImagem(): BelongsTo
    {
        return $this->belongsTo(CategoriaImagem::class);
    }

    public function blocos(): HasMany
    {
        return $this->hasMany(BlocoNovo::class, 'eixo_id');
    }

    public function especialidadesDistintivos(): BelongsToMany
    {
        return $this->belongsToMany(EspecialidadeDistintivo::class, 'especialidade_distintivo_eixo_novo');
    }
}
