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
        Schema::create('jovem_requisitos_complementares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jovem_id')->constrained('jovens')->cascadeOnDelete();
            $table->string('chave');
            $table->enum('tipo', ['booleano', 'contador']);
            $table->boolean('valor_booleano')->nullable();
            $table->unsignedSmallInteger('valor_numero')->nullable();
            $table->timestamps();

            $table->unique(['jovem_id', 'chave']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jovem_requisitos_complementares');
    }
};
