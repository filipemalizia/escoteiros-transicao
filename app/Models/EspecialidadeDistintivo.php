<?php

namespace App\Models;

use App\Services\ImagemDataUriService;
use Illuminate\Database\Eloquent\Builder;
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

    public function itensNovos(): HasMany
    {
        return $this->hasMany(ItemNovo::class, 'especialidade_id');
    }

    public function equivalenciasEspecialidade(): HasMany
    {
        return $this->hasMany(EquivalenciaEspecialidade::class);
    }

    /**
     * Se apagar, os itens novos vinculados a esta especialidade (e
     * progresso/equivalências ligados a eles), além das equivalências de
     * especialidade vinculadas, seriam apagados em cascata.
     */
    public function possuiItensComDadosVinculados(): bool
    {
        return $this->itensNovos->contains(fn (ItemNovo $item) => $item->possuiDadosVinculados())
            || $this->equivalenciasEspecialidade()->exists();
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
     * Ramo(s) a que esta especialidade/insígnia pertence diretamente, sem
     * passar por nenhum Eixo/Bloco — caso de insígnias do Ramo como um todo
     * (ex.: uma insígnia de Alcateia que não está ligada a nenhum Eixo
     * específico). Independente de `eixosNovos()`: uma especialidade pode
     * usar um, outro, ou os dois ao mesmo tempo.
     */
    public function ramos(): BelongsToMany
    {
        return $this->belongsToMany(Ramo::class, 'especialidade_distintivo_ramo');
    }

    /**
     * Especialidades/Insígnias disponíveis pro Ramo informado — via Eixo
     * (`eixosNovos`) OU vinculada direto ao Ramo (`ramos`), já que os dois
     * caminhos coexistem (ver {@see ramos()}).
     */
    public function scopeParaRamo(Builder $query, int $ramoId): Builder
    {
        return $query->where(
            fn (Builder $query) => $query
                ->whereHas('eixosNovos', fn (Builder $query) => $query->where('ramo_id', $ramoId))
                ->orWhereHas('ramos', fn (Builder $query) => $query->where('ramos.id', $ramoId))
        );
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

    /**
     * Mesma escolha de nível de {@see urlImagemParaNivel()}, mas como data
     * URI base64 em vez de URL — usado só pelo cartão de conquista
     * compartilhável (ver {@see ImagemDataUriService}).
     */
    public function dataUriImagemParaNivel(?int $nivelAtingido): ?string
    {
        $media = ($nivelAtingido >= 2 && $this->hasMedia('imagem_nivel_2'))
            ? $this->getFirstMedia('imagem_nivel_2')
            : $this->getFirstMedia('imagem_nivel_1');

        return app(ImagemDataUriService::class)->paraMedia($media);
    }
}
