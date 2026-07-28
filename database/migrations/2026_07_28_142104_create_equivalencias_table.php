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
        Schema::create('equivalencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_antigo_id')->constrained('itens_antigos')->cascadeOnDelete();
            $table->foreignId('item_novo_id')->constrained('itens_novos')->cascadeOnDelete();
            $table->enum('tipo_equivalencia', ['1-1', 'N-1', '1-N', 'sem_equivalencia']);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['item_antigo_id', 'item_novo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equivalencias');
    }
};
