<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EspecialidadeDistintivoGrupo extends Model
{
    protected $table = 'especialidade_distintivo_grupos';

    protected $fillable = ['especialidade_distintivo_id', 'chave', 'quantidade_minima', 'mensagem_regras', 'ordem'];

    public function especialidadeDistintivo(): BelongsTo
    {
        return $this->belongsTo(EspecialidadeDistintivo::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(EspecialidadeDistintivoItem::class, 'especialidade_distintivo_grupo_id');
    }

    /**
     * null = todos os itens do grupo são obrigatórios pra conquista.
     */
    public function exigeTodosOsItens(): bool
    {
        return is_null($this->quantidade_minima);
    }
}
