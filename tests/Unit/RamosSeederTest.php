<?php

use App\Models\Ramo;
use Database\Seeders\RamosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('e idempotente: rodar duas vezes nao duplica os ramos', function () {
    (new RamosSeeder)->run();
    (new RamosSeeder)->run();

    expect(Ramo::count())->toBe(4)
        ->and(Ramo::pluck('nome')->sort()->values()->all())
        ->toBe(['Escoteiro', 'Lobinho', 'Pioneiro', 'Sênior']);
});
