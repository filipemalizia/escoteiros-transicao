<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaDesenvolvimentoAntiga extends Model
{
    protected $table = 'areas_desenvolvimento_antigas';

    protected $fillable = ['ramo_id', 'nome'];

    public function ramo(): BelongsTo
    {
        return $this->belongsTo(Ramo::class);
    }

    public function competencias(): HasMany
    {
        return $this->hasMany(CompetenciaAntiga::class, 'area_desenvolvimento_id');
    }
}
