<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EixoNovo extends Model
{
    protected $table = 'eixos_novos';

    protected $fillable = ['ramo_id', 'nome'];

    public function ramo(): BelongsTo
    {
        return $this->belongsTo(Ramo::class);
    }

    public function blocos(): HasMany
    {
        return $this->hasMany(BlocoNovo::class, 'eixo_id');
    }
}
