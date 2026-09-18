<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EspecialidadeDistintivo extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'especialidades_distintivos';

    /**
     * Default aplicado pelo Eloquent — o default da coluna no banco continua
     * `'Básica'` (nome antigo, só dá pra trocar via ALTER específico de
     * dialeto). Só é relevante pra Insígnia; Especialidade nunca filtra por
     * modalidade.
     */
    protected $attributes = [
        'modalidade' => 'Geral',
    ];

    protected $fillable = [
        'nome',
        'tipo',
        'modalidade',
        'descricao',
        'estrutura',
        'regra_niveis',
        'minimo_nivel_1',
        'minimo_nivel_2',
        'sugestao_temas',
        'fonte_specialty_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sugestao_temas' => 'array',
        ];
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(EspecialidadeDistintivoGrupo::class);
    }

    /**
     * Ramo(s) + eixo(s) a que esta especialidade/insígnia pertence. Como
     * EixoNovo já é por ramo, ligar direto nele resolve os dois de uma vez —
     * ex.: uma especialidade Lobinho/Escoteiro liga ao "Meio Ambiente" do
     * Lobinho E ao "Meio Ambiente" do Escoteiro (dois registros).
     */
    public function eixosNovos(): BelongsToMany
    {
        return $this->belongsToMany(EixoNovo::class, 'especialidade_distintivo_eixo_novo');
    }

    /**
     * Imagem certa pro nível já atingido pelo jovem — usa a de nível 2
     * assim que ela existir e o nível 2 for atingido, senão cai pra nível 1
     * (que também serve de imagem única pra especialidades sem níveis, tipo
     * Insígnia/`atividades_temas`, onde $nivelAtingido chega como 0 ou 1).
     */
    public function urlImagemParaNivel(?int $nivelAtingido): ?string
    {
        if ($nivelAtingido >= 2 && $this->hasMedia('imagem_nivel_2')) {
            return $this->getFirstMediaUrl('imagem_nivel_2');
        }

        return $this->getFirstMediaUrl('imagem_nivel_1') ?: null;
    }
}
