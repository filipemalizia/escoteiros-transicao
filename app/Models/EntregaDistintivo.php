<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Controle da entrega FÍSICA do distintivo de uma Especialidade/Insígnia já
 * conquistada — desacoplado do progresso digital (`ProgressoEspecialidade`),
 * que é por requisito individual, não pela especialidade como um todo.
 * Só existe pro uso do chefe/admin; o jovem nunca vê isso.
 */
class EntregaDistintivo extends Model
{
    protected $table = 'entregas_distintivos';

    protected $fillable = [
        'jovem_id',
        'especialidade_distintivo_id',
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

    public function especialidadeDistintivo(): BelongsTo
    {
        return $this->belongsTo(EspecialidadeDistintivo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
