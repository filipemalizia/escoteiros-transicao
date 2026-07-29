<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function progressos(): HasMany
    {
        return $this->hasMany(ProgressoNovo::class);
    }

    public function equivalencias(): HasMany
    {
        return $this->hasMany(Equivalencia::class);
    }

    /**
     * Se apagar este item, perderia progresso já registrado por algum jovem
     * ou equivalências já cadastradas.
     */
    public function possuiDadosVinculados(): bool
    {
        return $this->progressos()->where('concluido', true)->exists()
            || $this->equivalencias()->exists();
    }
}
