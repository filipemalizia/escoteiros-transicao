<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
