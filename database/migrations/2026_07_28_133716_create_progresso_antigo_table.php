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
        Schema::create('progresso_antigo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jovem_id')->constrained('jovens')->cascadeOnDelete();
            $table->foreignId('item_antigo_id')->constrained('itens_antigos')->cascadeOnDelete();
            $table->boolean('concluido')->default(false);
            $table->date('data_conclusao')->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['jovem_id', 'item_antigo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progresso_antigo');
    }
};
