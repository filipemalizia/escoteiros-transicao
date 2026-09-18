<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EspecialidadeDistintivoItem extends Model
{
    protected $table = 'especialidade_distintivo_itens';

    protected $fillable = ['especialidade_distintivo_grupo_id', 'fonte_item_id', 'texto', 'ordem'];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(EspecialidadeDistintivoGrupo::class, 'especialidade_distintivo_grupo_id');
    }

    public function progressos(): HasMany
    {
        return $this->hasMany(ProgressoEspecialidade::class, 'especialidade_distintivo_item_id');
    }
}
