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
        Schema::create('blocos_novos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eixo_id')->constrained('eixos_novos')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->unsignedTinyInteger('quantidade_minima_variaveis')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocos_novos');
    }
};
