<?php

use App\Services\AcolhidaService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Período de Acolhida: itens que o jovem cumpre antes da Promessa (a
     * lista de itens de cada ramo fica em {@see AcolhidaService}).
     * A Promessa vale pro ramo em que foi feita (`promessa_ramo_id`): se o
     * jovem sobe de ramo, precisa registrar a nova.
     */
    public function up(): void
    {
        Schema::table('jovens', function (Blueprint $table) {
            $table->date('data_promessa')->nullable()->after('ramo_atual_id');
            $table->foreignId('promessa_ramo_id')->nullable()->after('data_promessa')->constrained('ramos')->nullOnDelete();
        });

        Schema::create('progresso_acolhida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jovem_id')->constrained('jovens')->cascadeOnDelete();
            $table->foreignId('ramo_id')->constrained('ramos')->cascadeOnDelete();
            $table->unsignedTinyInteger('ordem');
            $table->boolean('concluido')->default(false);
            $table->date('data_conclusao')->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('solicitado_pelo_jovem')->default(false);
            $table->timestamp('solicitado_em')->nullable();
            $table->text('observacao_jovem')->nullable();
            $table->timestamps();

            $table->unique(['jovem_id', 'ramo_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progresso_acolhida');

        Schema::table('jovens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promessa_ramo_id');
            $table->dropColumn('data_promessa');
        });
    }
};
