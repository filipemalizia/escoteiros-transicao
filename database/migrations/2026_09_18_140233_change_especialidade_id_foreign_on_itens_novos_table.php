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
        Schema::table('itens_novos', function (Blueprint $table) {
            $table->dropForeign(['especialidade_id']);
            $table->foreign('especialidade_id')->references('id')->on('especialidades_distintivos')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itens_novos', function (Blueprint $table) {
            $table->dropForeign(['especialidade_id']);
            $table->foreign('especialidade_id')->references('id')->on('especialidades_distintivos');
        });
    }
};
