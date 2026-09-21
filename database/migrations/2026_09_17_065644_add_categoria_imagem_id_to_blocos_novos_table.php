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
        Schema::table('blocos_novos', function (Blueprint $table) {
            $table->foreignId('categoria_imagem_id')->nullable()->constrained('categorias_imagem')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blocos_novos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_imagem_id');
        });
    }
};
