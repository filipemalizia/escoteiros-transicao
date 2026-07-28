<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemNovo extends Model
{
    protected $table = 'itens_novos';

    protected $fillable = [
        'bloco_id',
        'codigo',
        'descricao',
        'tipo_acao',
        'modalidade',
        'especialidade_id',
        'observacao',
    ];

    public function bloco(): BelongsTo
    {
        return $this->belongsTo(BlocoNovo::class, 'bloco_id');
    }

    public function especialidade(): BelongsTo
    {
        return $this->belongsTo(EspecialidadeDistintivo::class, 'especialidade_id');
    }
}
