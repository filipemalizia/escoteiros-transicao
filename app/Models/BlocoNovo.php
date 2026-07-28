<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlocoNovo extends Model
{
    protected $table = 'blocos_novos';

    protected $fillable = ['eixo_id', 'titulo', 'descricao', 'quantidade_minima_variaveis'];

    public function eixo(): BelongsTo
    {
        return $this->belongsTo(EixoNovo::class, 'eixo_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemNovo::class, 'bloco_id');
    }
}
