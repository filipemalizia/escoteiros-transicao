<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sintaxe MySQL específica; SQLite (usado nos testes) não impõe o enum
        // via CHECK aqui, então não há nada equivalente a rodar nesse driver.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE equivalencias MODIFY tipo_equivalencia ENUM('1-1', 'N-1', '1-N') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE equivalencias MODIFY tipo_equivalencia ENUM('1-1', 'N-1', '1-N', 'sem_equivalencia') NOT NULL");
        }
    }
};
