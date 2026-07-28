<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota para fase futura: o Reconhecimento (conquista final do Ramo Sênior no
 * programa antigo) não é modelável apenas com "% de Itens concluídos". Exige
 * uma regra composta: 100% da Etapa Azimute + Cordão Dourado + 1 Insígnia de
 * Interesse Especial (Mundial do Meio Ambiente, Lusofonia, Cone Sul ou
 * Desafio Comunitário) + mínimo 10 noites de acampamento como Sênior + 1
 * Insígnia de Modalidade (Aeronauta, Naval ou Mateiro) + recomendação dos
 * escotistas e da Corte de Honra da Tropa. Vai precisar de campos próprios
 * (contador de noites de acampamento, registro de insígnias, flag de
 * recomendação) — não implementado ainda.
 */
class Jovem extends Model
{
    protected $table = 'jovens';

    protected $fillable = ['nome', 'data_nascimento', 'ramo_atual_id'];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    public function ramoAtual(): BelongsTo
    {
        return $this->belongsTo(Ramo::class, 'ramo_atual_id');
    }
}
