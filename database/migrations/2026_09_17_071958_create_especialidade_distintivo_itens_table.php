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
        Schema::create('especialidade_distintivo_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidade_distintivo_grupo_id')
                ->constrained('especialidade_distintivo_grupos', indexName: 'edi_grupo_id_foreign')
                ->cascadeOnDelete();
            $table->unsignedInteger('fonte_item_id')->nullable();
            $table->text('texto');
            $table->unsignedInteger('ordem')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('especialidade_distintivo_itens');
    }
};
