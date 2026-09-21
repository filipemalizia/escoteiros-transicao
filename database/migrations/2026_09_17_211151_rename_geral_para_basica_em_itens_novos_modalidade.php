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
        // O default antigo da coluna ('Geral') fica inofensivo depois desta
        // migration — todo lugar que cria ItemNovo (formulário do Filament,
        // ImportadorNovoService) já foi ajustado pra sempre gravar 'Básica'
        // explicitamente, então nenhum código novo depende do default da
        // coluna em si (evita um ALTER de DEFAULT específico de dialeto SQL,
        // que o SQLite dos testes não suporta na mesma sintaxe do MySQL).
        DB::table('itens_novos')->where('modalidade', 'Geral')->update(['modalidade' => 'Básica']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('itens_novos')->where('modalidade', 'Básica')->update(['modalidade' => 'Geral']);
    }
};
