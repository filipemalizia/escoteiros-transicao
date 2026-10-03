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
        Schema::table('equivalencia_especialidades', function (Blueprint $table) {
            $table->unsignedTinyInteger('nivel_minimo')->nullable()->after('item_novo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equivalencia_especialidades', function (Blueprint $table) {
            $table->dropColumn('nivel_minimo');
        });
    }
};
