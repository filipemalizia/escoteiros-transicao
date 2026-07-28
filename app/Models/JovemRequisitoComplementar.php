<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JovemRequisitoComplementar extends Model
{
    protected $table = 'jovem_requisitos_complementares';

    protected $fillable = ['jovem_id', 'chave', 'tipo', 'valor_booleano', 'valor_numero'];

    protected $casts = [
        'valor_booleano' => 'boolean',
    ];

    public function jovem(): BelongsTo
    {
        return $this->belongsTo(Jovem::class);
    }
}
