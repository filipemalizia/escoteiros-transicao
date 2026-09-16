<?php

use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);
});

it('bloqueia apos varias tentativas erradas do mesmo ip', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('portal.login'), [
            'registro' => '123456',
            'data_nascimento' => '1999-01-01',
        ])->assertSessionHasErrors('registro');
    }

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '1999-01-01',
    ])->assertStatus(429);
});
