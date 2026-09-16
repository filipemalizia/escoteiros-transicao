<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jovem extends Model
{
    protected $table = 'jovens';

    protected $fillable = ['nome', 'registro', 'data_nascimento', 'ramo_atual_id', 'equipe_id'];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    public function ramoAtual(): BelongsTo
    {
        return $this->belongsTo(Ramo::class, 'ramo_atual_id');
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }

    public function progressoAntigo(): HasMany
    {
        return $this->hasMany(ProgressoAntigo::class);
    }

    public function progressoNovo(): HasMany
    {
        return $this->hasMany(ProgressoNovo::class);
    }

    public function requisitosComplementares(): HasMany
    {
        return $this->hasMany(JovemRequisitoComplementar::class);
    }

    public function itensPersonalizados(): BelongsToMany
    {
        return $this->belongsToMany(ItemPersonalizado::class, 'item_personalizado_jovem');
    }

    public function progressoPersonalizado(): HasMany
    {
        return $this->hasMany(ProgressoPersonalizado::class);
    }

    public function requisito(string $chave): ?JovemRequisitoComplementar
    {
        return $this->requisitosComplementares->firstWhere('chave', $chave);
    }

    public function requisitoBool(string $chave): bool
    {
        return (bool) ($this->requisito($chave)?->valor_booleano ?? false);
    }

    public function requisitoNumero(string $chave): int
    {
        return (int) ($this->requisito($chave)?->valor_numero ?? 0);
    }
}
