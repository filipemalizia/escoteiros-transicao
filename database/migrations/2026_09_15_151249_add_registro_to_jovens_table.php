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
        Schema::table('jovens', function (Blueprint $table) {
            // Nullable no banco porque jovens já cadastrados não têm esse
            // dado ainda (precisa ser preenchido manualmente por um líder).
            // Guardado como string (não integer) pra não perder zeros à
            // esquerda; a validação de "só dígitos" fica no formulário.
            $table->string('registro')->nullable()->unique()->after('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jovens', function (Blueprint $table) {
            $table->dropUnique(['registro']);
            $table->dropColumn('registro');
        });
    }
};
