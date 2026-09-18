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
            $table->timestamp('portal_visitado_em')->nullable()->after('ramo_atual_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jovens', function (Blueprint $table) {
            $table->dropColumn('portal_visitado_em');
        });
    }
};
