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
            $table->boolean('marcado_para_fazer')->default(false)->after('observacao_jovem');
            $table->timestamp('marcado_para_fazer_em')->nullable()->after('marcado_para_fazer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progresso_personalizado', function (Blueprint $table) {
            $table->dropColumn(['marcado_para_fazer', 'marcado_para_fazer_em']);
        });
    }
};
