<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('email');
            });
        }

        // Só um admin pode gerenciar usuários/equipes/jovens fora da própria
        // equipe (ver JovemPolicy/UserPolicy/EquipePolicy). Sem isso, ninguém
        // conseguiria promover o primeiro admin depois do deploy — então o
        // usuário mais antigo já existente vira admin automaticamente.
        $primeiroUsuarioId = DB::table('users')->oldest('id')->value('id');

        if ($primeiroUsuarioId !== null) {
            DB::table('users')->where('id', $primeiroUsuarioId)->update(['is_admin' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
