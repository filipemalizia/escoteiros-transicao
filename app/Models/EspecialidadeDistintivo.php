<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspecialidadeDistintivo extends Model
{
    protected $table = 'especialidades_distintivos';

    protected $fillable = ['nome', 'tipo'];
}
