<?php

namespace App\Models;

use App\Services\AcolhidaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um item do Período de Acolhida cumprido (ou pedido pra avaliação) por um
 * jovem num ramo. O item em si é identificado pela `ordem` na lista do ramo
 * ({@see AcolhidaService::ITENS_POR_RAMO}), não por uma tabela de itens.
 */
class ProgressoAcolhida extends Model
{
    protected $table = 'progresso_acolhida';

    protected $fillable = [
        'jovem_id',
        'ramo_id',
        'ordem',
        'concluido',
        'data_conclusao',
        'registrado_por_id',
        'solicitado_pelo_jovem',
        'solicitado_em',
        'observacao_jovem',
    ];

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

    public function ramo(): BelongsTo
    {
        return $this->belongsTo(Ramo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    /**
     * Texto do item, pra as telas que mostram "qualquer item" pelo mesmo
     * campo `descricao` (ex.: modal de avaliação do chefe).
     */
    public function getDescricaoAttribute(): string
    {
        return app(AcolhidaService::class)->textoDoItem((int) $this->ramo_id, (int) $this->ordem) ?? '';
    }
}
