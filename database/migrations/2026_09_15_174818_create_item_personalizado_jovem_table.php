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
        Schema::create('item_personalizado_jovem', function (Blueprint $table) {
            $table->foreignId('item_personalizado_id')->constrained('itens_personalizados')->cascadeOnDelete();
            $table->foreignId('jovem_id')->constrained('jovens')->cascadeOnDelete();

            $table->primary(['item_personalizado_id', 'jovem_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_personalizado_jovem');
    }
};
