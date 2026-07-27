<?php

namespace Database\Seeders;

use App\Models\Ramo;
use Illuminate\Database\Seeder;

class RamosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Lobinho', 'Escoteiro', 'Sênior', 'Pioneiro'] as $nome) {
            Ramo::create(['nome' => $nome]);
        }
    }
}
