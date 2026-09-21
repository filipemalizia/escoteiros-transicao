<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquivalenciaEspecialidade extends Model
{
    protected $fillable = ['especialidade_distintivo_id', 'item_novo_id', 'observacao'];

    public function especialidadeDistintivo(): BelongsTo
    {
        return $this->belongsTo(EspecialidadeDistintivo::class);
    }

    public function itemNovo(): BelongsTo
    {
        return $this->belongsTo(ItemNovo::class);
    }
}
