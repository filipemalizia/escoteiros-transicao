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
            $table->foreignId('equipe_id')->nullable()->after('ramo_atual_id')->constrained('equipes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jovens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipe_id');
        });
    }
};
