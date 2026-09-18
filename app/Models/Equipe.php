<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipe extends Model
{
    protected $fillable = ['ramo_id', 'nome', 'modalidade'];

    public function ramo(): BelongsTo
    {
        return $this->belongsTo(Ramo::class);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'equipe_user');
    }

    public function jovens(): HasMany
    {
        return $this->hasMany(Jovem::class);
    }
}
