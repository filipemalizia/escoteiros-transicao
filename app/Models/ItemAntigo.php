<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemAntigo extends Model
{
    protected $table = 'itens_antigos';

    protected $fillable = ['competencia_id', 'codigo', 'descricao', 'etapa', 'introdutorio'];

    protected $casts = [
        'introdutorio' => 'boolean',
    ];

    public function competencia(): BelongsTo
    {
        return $this->belongsTo(CompetenciaAntiga::class);
    }

    public function progressos(): HasMany
    {
        return $this->hasMany(ProgressoAntigo::class);
    }

    public function equivalencias(): HasMany
    {
        return $this->hasMany(Equivalencia::class);
    }

    public function equivalenciasBloco(): HasMany
    {
        return $this->hasMany(EquivalenciaBloco::class);
    }

    /**
     * Se apagar este item, perderia progresso já registrado por algum jovem
     * ou equivalências (item-a-item ou de bloco) já cadastradas.
     */
    public function possuiDadosVinculados(): bool
    {
        return $this->progressos()->where('concluido', true)->exists()
            || $this->equivalencias()->exists()
            || $this->equivalenciasBloco()->exists();
    }
}
