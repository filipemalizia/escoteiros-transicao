<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquivalenciaBloco extends Model
{
    protected $fillable = ['item_antigo_id', 'bloco_novo_id', 'observacao'];

    public function itemAntigo(): BelongsTo
    {
        return $this->belongsTo(ItemAntigo::class);
    }

    public function blocoNovo(): BelongsTo
    {
        return $this->belongsTo(BlocoNovo::class, 'bloco_novo_id');
    }
}
