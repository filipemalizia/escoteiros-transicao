<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressoNovo extends Model
{
    protected $table = 'progresso_novo';

    protected $fillable = ['jovem_id', 'item_novo_id', 'concluido', 'data_conclusao', 'registrado_por_id', 'solicitado_pelo_jovem', 'solicitado_em', 'observacao_jovem'];

    protected $casts = [
        'concluido' => 'boolean',
        'data_conclusao' => 'date',
        'solicitado_pelo_jovem' => 'boolean',
        'solicitado_em' => 'datetime',
    ];

    public function jovem(): BelongsTo
    {
        return $this->belongsTo(Jovem::class);
    }

    public function itemNovo(): BelongsTo
    {
        return $this->belongsTo(ItemNovo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
