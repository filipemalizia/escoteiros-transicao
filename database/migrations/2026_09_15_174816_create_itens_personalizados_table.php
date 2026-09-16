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
        Schema::create('itens_personalizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloco_novo_id')->constrained('blocos_novos')->cascadeOnDelete();
            $table->text('descricao');
            // nullOnDelete: se o adulto que criou for removido, o item
            // personalizado continua existindo (não é dado do adulto).
            $table->foreignId('criado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itens_personalizados');
    }
};
