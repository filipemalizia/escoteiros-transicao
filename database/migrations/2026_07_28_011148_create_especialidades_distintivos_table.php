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
        Schema::create('especialidades_distintivos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['Especialidade', 'Insígnia']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('especialidades_distintivos');
    }
};
