<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Item "Variável" avulso, criado por um adulto pra um ou mais jovens
 * específicos — não faz parte do catálogo oficial (`ItemNovo`), por isso
 * fica numa tabela própria. Conta na cota de Ações Variáveis do bloco
 * (ver `StatusProgressaoService::statusBloco()`).
 */
class ItemPersonalizado extends Model
{
    protected $table = 'itens_personalizados';

    protected $fillable = ['bloco_novo_id', 'descricao', 'criado_por_id'];

    public function bloco(): BelongsTo
    {
        return $this->belongsTo(BlocoNovo::class, 'bloco_novo_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    public function jovens(): BelongsToMany
    {
        return $this->belongsToMany(Jovem::class, 'item_personalizado_jovem');
    }

    public function progresso(): HasMany
    {
        return $this->hasMany(ProgressoPersonalizado::class);
    }

    /**
     * Não tem código de catálogo oficial — este "pseudo-código" existe só
     * pra esse model conseguir aparecer nas mesmas listas/PDF que
     * ItemAntigo/ItemNovo (que têm `codigo` de verdade) sem precisar de
     * `@if` espalhado pelas views checando o tipo do item.
     */
    protected function codigo(): Attribute
    {
        return Attribute::get(fn () => 'PERS-'.$this->id);
    }
}
