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

    public function equivalenciasBloco(): HasMany
    {
        return $this->hasMany(EquivalenciaBloco::class, 'bloco_novo_id');
    }

    /**
     * Se apagar, os itens deste bloco (e progresso/equivalências ligadas a
     * eles), além das equivalências de bloco vinculadas, seriam apagados em
     * cascata.
     */
    public function possuiItensComDadosVinculados(): bool
    {
        return $this->itens->contains(fn (ItemNovo $item) => $item->possuiDadosVinculados())
            || $this->equivalenciasBloco()->exists();
    }
}
