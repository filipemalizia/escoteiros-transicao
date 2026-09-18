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
        Schema::create('especialidade_distintivo_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidade_distintivo_id')
                ->constrained('especialidades_distintivos', indexName: 'edg_especialidade_distintivo_id_foreign')
                ->cascadeOnDelete();
            $table->string('chave'); // 'itens' | 'conhecer' | 'fazer' | 'compartilhar' | outro nome livre
            // null = todos os itens do grupo são obrigatórios; um número = mínimo entre os itens do grupo (igual quantidade_minima_variaveis do Bloco).
            $table->unsignedTinyInteger('quantidade_minima')->nullable();
            $table->unsignedInteger('ordem')->nullable();
            $table->timestamps();

            $table->unique(['especialidade_distintivo_id', 'chave'], 'edg_especialidade_id_chave_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('especialidade_distintivo_grupos');
    }
};
