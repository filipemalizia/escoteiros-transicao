<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 'Básica' veio do default da coluna (mesmo nome usado em
        // itens_novos/equipes) — mas pra Insígnia o termo que faz mais
        // sentido é 'Geral' (aparece pra todo mundo, independente da
        // modalidade do jovem). Especialidade nunca filtra por modalidade,
        // então o valor não afeta nada nela.
        DB::table('especialidades_distintivos')->where('modalidade', 'Básica')->update(['modalidade' => 'Geral']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('especialidades_distintivos')->where('modalidade', 'Geral')->update(['modalidade' => 'Básica']);
    }
};
