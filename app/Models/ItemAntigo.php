<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemAntigo extends Model
{
    protected $table = 'itens_antigos';

    protected $fillable = ['competencia_id', 'codigo', 'descricao'];

    public function competencia(): BelongsTo
    {
        return $this->belongsTo(CompetenciaAntiga::class);
    }
}
