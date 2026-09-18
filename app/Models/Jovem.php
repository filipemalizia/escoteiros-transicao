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

    /**
     * Quando a Equipe do jovem muda, o Ramo dele acompanha automaticamente
     * (uma equipe pertence sempre a um ramo só) — evita o jovem ficar com
     * Ramo desatualizado depois de ser movido pra uma equipe de outro ramo
     * (ex.: subiu de Sênior pra Pioneiro). Roda em qualquer caminho que
     * altere `equipe_id` (form do Jovem, Associar/Criar na Equipe), porque
     * é um evento do model, não de uma tela específica.
     */
    protected static function booted(): void
    {
        static::saving(function (Jovem $jovem) {
            if ($jovem->isDirty('equipe_id') && $jovem->equipe_id) {
                $jovem->ramo_atual_id = Equipe::find($jovem->equipe_id)?->ramo_id ?? $jovem->ramo_atual_id;
            }
        });
    }

    public function ramoAtual(): BelongsTo
    {
        return $this->belongsTo(Ramo::class, 'ramo_atual_id');
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }

    /**
     * Básica/Ar/Mar — herdada da Equipe (uma equipe é sempre de uma
     * modalidade só). Jovem sem equipe cadastrada cai em 'Básica', o valor
     * seguro que nunca esconde nem libera item de Ar/Mar por engano.
     */
    public function modalidade(): string
    {
        return $this->equipe?->modalidade ?? 'Básica';
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
