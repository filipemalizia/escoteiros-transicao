<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressoPersonalizado extends Model
{
    protected $table = 'progresso_personalizado';

    protected $fillable = [
        'jovem_id',
        'item_personalizado_id',
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

    public function itemPersonalizado(): BelongsTo
    {
        return $this->belongsTo(ItemPersonalizado::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
