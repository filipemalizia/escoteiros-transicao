<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ramo extends Model
{
    protected $fillable = ['nome'];

    public function jovens(): HasMany
    {
        return $this->hasMany(Jovem::class, 'ramo_atual_id');
    }

    public function especialidadesDistintivos(): BelongsToMany
    {
        return $this->belongsToMany(EspecialidadeDistintivo::class, 'especialidade_distintivo_ramo');
    }
}
