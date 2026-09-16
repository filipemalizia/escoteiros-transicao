<?php

use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('permite acesso ao progresso dentro da janela da sessao', function () {
    $this->get(route('portal.progresso'))->assertOk();
});

it('redireciona pro login quando a sessao do portal expirou', function () {
    Carbon::setTestNow(now()->addMinutes(config('portal.sessao_minutos') + 1));

    $this->get(route('portal.progresso'))->assertRedirect(route('portal.login.mostrar'));
});

it('bloqueia o acesso ao progresso sem nunca ter autenticado', function () {
    $this->flushSession();

    $this->get(route('portal.progresso'))->assertRedirect(route('portal.login.mostrar'));
});
