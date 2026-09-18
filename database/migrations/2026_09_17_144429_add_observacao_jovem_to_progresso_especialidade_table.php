<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('progresso_especialidade', function (Blueprint $table) {
            $table->text('observacao_jovem')->nullable()->after('solicitado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progresso_especialidade', function (Blueprint $table) {
            $table->dropColumn('observacao_jovem');
        });
    }
};
