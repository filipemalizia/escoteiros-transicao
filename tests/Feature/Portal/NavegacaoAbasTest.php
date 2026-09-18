<?php

use App\Livewire\Portal\Revisao;
use App\Livewire\Portal\Timeline;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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

it('bloqueia acesso as novas abas sem sessao valida', function () {
    Livewire::test(Timeline::class)->assertForbidden();
    Livewire::test(Revisao::class)->assertForbidden();
});

it('permite acesso a linha do tempo e revisao depois de autenticado', function () {
    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Timeline::class)->assertOk()->assertSee('Linha do Tempo');
    Livewire::test(Revisao::class)->assertOk()->assertSee('Revisão');
});

it('mostra a barra de abas nas 3 paginas do portal autenticado, mas nao na tela de login', function () {
    $this->get(route('portal.login.mostrar'))->assertDontSee('Linha do Tempo');

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);

    // Livewire::test() renderiza só o componente, sem o layout onde a barra
    // de abas mora — por isso a asserção aqui é via requisição HTTP normal.
    $this->get(route('portal.progresso'))->assertSee('Linha do Tempo')->assertSee('Revisão');
    $this->get(route('portal.timeline'))->assertSee('Início')->assertSee('Revisão');
    $this->get(route('portal.revisao'))->assertSee('Início')->assertSee('Linha do Tempo');
});
