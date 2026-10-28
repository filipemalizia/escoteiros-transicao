<?php

use App\Concerns\ControlaDesmarcacaoQueroFazer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedido do pioneiro pra desmarcar um "quero fazer" já com mais de 24h
     * (ver {@see ControlaDesmarcacaoQueroFazer}).
     */
    public function up(): void
    {
        foreach (['progresso_novo', 'progresso_personalizado'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->timestamp('desmarcar_solicitado_em')->nullable()->after('marcado_para_fazer_em');
            });
        }
    }

    public function down(): void
    {
        foreach (['progresso_novo', 'progresso_personalizado'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn('desmarcar_solicitado_em');
            });
        }
    }
};
