<?php

namespace App\Models;

use App\Services\EtapaProgressaoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Controle da entrega FÍSICA do distintivo de Etapa/Reconhecimento (Pata
 * Tenra, Lis de Ouro, etc.) já alcançada — mesma ideia de
 * {@see EntregaDistintivo}, só que pra Etapa em vez de Especialidade/Insígnia.
 * `etapa` guarda o nome do marco (ex.: "Pata Tenra", "Cruzeiro do Sul"), não
 * um id, porque Etapa não tem tabela própria — é só calculada
 * (ver {@see EtapaProgressaoService::trilhaEtapaNovo()}).
 */
class EntregaEtapa extends Model
{
    protected $table = 'entregas_etapas';

    protected $fillable = [
        'jovem_id',
        'etapa',
        'comprado_em',
        'entregue_em',
        'registrado_por_id',
    ];

    protected $casts = [
        'comprado_em' => 'date',
        'entregue_em' => 'date',
    ];

    public function jovem(): BelongsTo
    {
        return $this->belongsTo(Jovem::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
