<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetenciaAntiga extends Model
{
    protected $table = 'competencias_antigas';

    protected $fillable = ['area_desenvolvimento_id', 'descricao'];

    public function areaDesenvolvimento(): BelongsTo
    {
        return $this->belongsTo(AreaDesenvolvimentoAntiga::class, 'area_desenvolvimento_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemAntigo::class, 'competencia_id');
    }

    /**
     * Se apagar, os itens desta competência (e progresso/equivalências
     * ligadas a eles) seriam apagados em cascata.
     */
    public function possuiItensComDadosVinculados(): bool
    {
        return $this->itens->contains(fn (ItemAntigo $item) => $item->possuiDadosVinculados());
    }
}
