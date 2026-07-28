<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equivalencia extends Model
{
    protected $fillable = ['item_antigo_id', 'item_novo_id', 'tipo_equivalencia', 'observacao'];

    public function itemAntigo(): BelongsTo
    {
        return $this->belongsTo(ItemAntigo::class);
    }

    public function itemNovo(): BelongsTo
    {
        return $this->belongsTo(ItemNovo::class);
    }
}
