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
        Schema::table('progresso_personalizado', function (Blueprint $table) {
            $table->date('data_alvo')->nullable()->after('marcado_para_fazer_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progresso_personalizado', function (Blueprint $table) {
            $table->dropColumn('data_alvo');
        });
    }
};
