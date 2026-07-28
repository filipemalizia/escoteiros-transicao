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
        Schema::create('equivalencia_blocos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_antigo_id')->constrained('itens_antigos')->cascadeOnDelete();
            $table->foreignId('bloco_novo_id')->constrained('blocos_novos')->cascadeOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['item_antigo_id', 'bloco_novo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equivalencia_blocos');
    }
};
