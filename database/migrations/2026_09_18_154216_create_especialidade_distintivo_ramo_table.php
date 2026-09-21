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
        Schema::create('especialidade_distintivo_ramo', function (Blueprint $table) {
            $table->foreignId('especialidade_distintivo_id')
                ->constrained('especialidades_distintivos', indexName: 'edxr_especialidade_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('ramo_id')->constrained('ramos')->cascadeOnDelete();

            $table->primary(['especialidade_distintivo_id', 'ramo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('especialidade_distintivo_ramo');
    }
};
