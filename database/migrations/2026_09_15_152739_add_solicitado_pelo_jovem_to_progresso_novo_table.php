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
        Schema::table('progresso_novo', function (Blueprint $table) {
            $table->boolean('solicitado_pelo_jovem')->default(false)->after('registrado_por_id');
            $table->timestamp('solicitado_em')->nullable()->after('solicitado_pelo_jovem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progresso_novo', function (Blueprint $table) {
            $table->dropColumn(['solicitado_pelo_jovem', 'solicitado_em']);
        });
    }
};
