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
        Schema::create('progresso_especialidade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jovem_id')->constrained('jovens')->cascadeOnDelete();
            $table->foreignId('especialidade_distintivo_item_id')
                ->constrained('especialidade_distintivo_itens', indexName: 'progresso_especialidade_item_id_foreign')
                ->cascadeOnDelete();
            $table->boolean('concluido')->default(false);
            $table->date('data_conclusao')->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('solicitado_pelo_jovem')->default(false);
            $table->timestamp('solicitado_em')->nullable();
            $table->timestamps();

            $table->unique(['jovem_id', 'especialidade_distintivo_item_id'], 'progresso_especialidade_jovem_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progresso_especialidade');
    }
};
