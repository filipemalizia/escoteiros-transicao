<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressoEspecialidade extends Model
{
    protected $table = 'progresso_especialidade';

    protected $fillable = [
        'jovem_id',
        'especialidade_distintivo_item_id',
        'concluido',
        'data_conclusao',
        'registrado_por_id',
        'solicitado_pelo_jovem',
        'solicitado_em',
        'observacao_jovem',
        'marcado_para_fazer',
        'marcado_para_fazer_em',
        'data_alvo',
    ];

    protected $casts = [
        'concluido' => 'boolean',
        'data_conclusao' => 'date',
        'solicitado_pelo_jovem' => 'boolean',
        'solicitado_em' => 'datetime',
        'marcado_para_fazer' => 'boolean',
        'marcado_para_fazer_em' => 'datetime',
        'data_alvo' => 'date',
    ];

    public function jovem(): BelongsTo
    {
        return $this->belongsTo(Jovem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(EspecialidadeDistintivoItem::class, 'especialidade_distintivo_item_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
