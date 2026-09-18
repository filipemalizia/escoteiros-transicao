<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EixoNovo extends Model
{
    protected $table = 'eixos_novos';

    protected $fillable = ['ramo_id', 'nome', 'categoria_imagem_id'];

    /**
     * Cor oficial de cada Eixo (mesmo nome se repete em todos os ramos) —
     * usada no portal do jovem pra colorir bolinhas/barras de progresso com
     * a identidade visual de cada eixo em vez de uma cor genérica única.
     */
    private const CORES = [
        'Meio Ambiente' => '#4db05b',
        'Paz e Desenvolvimento' => '#194383',
        'Habilidades para a Vida' => '#e73458',
        'Saúde e Bem-estar' => '#e2947b',
    ];

    /**
     * Cinza neutro pra qualquer eixo sem cor mapeada (evita quebrar se um
     * eixo novo for cadastrado com um nome diferente dos 4 oficiais).
     */
    public function cor(): string
    {
        return self::CORES[$this->nome] ?? '#6b7280';
    }

    public function ramo(): BelongsTo
    {
        return $this->belongsTo(Ramo::class);
    }

    public function categoriaImagem(): BelongsTo
    {
        return $this->belongsTo(CategoriaImagem::class);
    }

    public function blocos(): HasMany
    {
        return $this->hasMany(BlocoNovo::class, 'eixo_id');
    }

    public function especialidadesDistintivos(): BelongsToMany
    {
        return $this->belongsToMany(EspecialidadeDistintivo::class, 'especialidade_distintivo_eixo_novo');
    }
}
