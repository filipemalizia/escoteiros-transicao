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
        Schema::create('itens_novos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloco_id')->constrained('blocos_novos')->cascadeOnDelete();
            $table->string('codigo')->unique();
            $table->text('descricao');
            $table->enum('tipo_acao', ['Obrigatória', 'Variável', 'Substitutiva']);
            $table->string('modalidade')->nullable()->default('Geral');
            $table->foreignId('especialidade_id')->nullable()->constrained('especialidades_distintivos');
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itens_novos');
    }
};
