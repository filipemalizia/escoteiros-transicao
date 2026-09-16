<?php

use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);
});

it('autentica com registro e data de nascimento corretos e redireciona pro progresso', function () {
    $response = $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    $response->assertRedirect(route('portal.progresso'));
    $this->assertTrue(session()->has('portal_jovem_id'));
});

it('nao autentica com data de nascimento errada e nao inicia sessao', function () {
    $response = $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2011-01-01',
    ]);

    $response->assertSessionHasErrors('registro');
    $this->assertFalse(session()->has('portal_jovem_id'));
});

it('nao autentica com registro inexistente', function () {
    $response = $this->post(route('portal.login'), [
        'registro' => '999999',
        'data_nascimento' => '2010-05-20',
    ]);

    $response->assertSessionHasErrors('registro');
    $this->assertFalse(session()->has('portal_jovem_id'));
});

it('regenera o id da sessao ao autenticar', function () {
    $idAntes = session()->getId();

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    expect(session()->getId())->not->toBe($idAntes);
});
