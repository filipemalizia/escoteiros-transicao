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
        Schema::table('especialidades_distintivos', function (Blueprint $table) {
            $table->text('descricao')->nullable();
            $table->string('estrutura')->nullable(); // 'itens_niveis' | 'atividades_temas'
            $table->text('regra_niveis')->nullable();
            $table->unsignedTinyInteger('minimo_nivel_1')->nullable();
            $table->unsignedTinyInteger('minimo_nivel_2')->nullable();
            $table->json('sugestao_temas')->nullable();
            $table->unsignedInteger('fonte_specialty_id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('especialidades_distintivos', function (Blueprint $table) {
            $table->dropUnique(['fonte_specialty_id']);
            $table->dropColumn([
                'descricao',
                'estrutura',
                'regra_niveis',
                'minimo_nivel_1',
                'minimo_nivel_2',
                'sugestao_temas',
                'fonte_specialty_id',
            ]);
        });
    }
};
