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
        Schema::table('especialidade_distintivo_grupos', function (Blueprint $table) {
            $table->text('mensagem_regras')->nullable()->after('quantidade_minima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('especialidade_distintivo_grupos', function (Blueprint $table) {
            $table->dropColumn('mensagem_regras');
        });
    }
};
