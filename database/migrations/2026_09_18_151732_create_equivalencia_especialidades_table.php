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
        Schema::create('equivalencia_especialidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidade_distintivo_id')->constrained('especialidades_distintivos')->cascadeOnDelete();
            $table->foreignId('item_novo_id')->constrained('itens_novos')->cascadeOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['especialidade_distintivo_id', 'item_novo_id'], 'equivalencia_especialidades_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equivalencia_especialidades');
    }
};
