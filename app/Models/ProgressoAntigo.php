<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressoAntigo extends Model
{
    protected $table = 'progresso_antigo';

    protected $fillable = ['jovem_id', 'item_antigo_id', 'concluido', 'data_conclusao', 'registrado_por_id'];

    protected $casts = [
        'concluido' => 'boolean',
        'data_conclusao' => 'date',
    ];

    public function jovem(): BelongsTo
    {
        return $this->belongsTo(Jovem::class);
    }

    public function itemAntigo(): BelongsTo
    {
        return $this->belongsTo(ItemAntigo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
