<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

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

describe('bloqueio por tentativas erradas', function () {
    beforeEach(function () {
        // O limite por minuto/IP não é o que está em teste aqui.
        $this->withoutMiddleware(ThrottleRequests::class);
        config(['portal.login_max_falhas' => 3, 'portal.login_janela_minutos' => 60]);
    });

    $errar = fn ($teste, string $registro = '123456') => $teste->post(route('portal.login'), [
        'registro' => $registro,
        'data_nascimento' => '1999-01-01',
    ]);

    it('bloqueia o registro depois de varias falhas, mesmo com a data certa', function () use ($errar) {
        foreach (range(1, 3) as $i) {
            $errar($this)->assertSessionHasErrors(['registro' => 'Registro ou data de nascimento incorretos.']);
        }

        $this->post(route('portal.login'), ['registro' => '123456', 'data_nascimento' => '2010-05-20'])
            ->assertSessionHasErrors('registro')
            ->assertSessionMissing('portal_jovem_id');

        expect(session('errors')->first('registro'))->toContain('Muitas tentativas');
    });

    it('nao bloqueia outros registros', function () use ($errar) {
        foreach (range(1, 3) as $i) {
            $errar($this);
        }

        $outro = Jovem::create(['nome' => 'Outro', 'registro' => '999999', 'data_nascimento' => '2011-01-01', 'ramo_atual_id' => Ramo::first()->id]);

        $this->post(route('portal.login'), ['registro' => '999999', 'data_nascimento' => '2011-01-01'])
            ->assertRedirect(route('portal.progresso'));
    });

    it('um acerto zera a contagem de falhas', function () use ($errar) {
        $errar($this);
        $errar($this);

        $this->post(route('portal.login'), ['registro' => '123456', 'data_nascimento' => '2010-05-20'])
            ->assertRedirect(route('portal.progresso'));

        $errar($this);
        $errar($this);

        $this->post(route('portal.login'), ['registro' => '123456', 'data_nascimento' => '2010-05-20'])
            ->assertRedirect(route('portal.progresso'));
    });

    it('trata o registro de forma igual com maiusculas e espacos, e tambem bloqueia registro inexistente', function () use ($errar) {
        foreach (['ABC', ' abc ', 'Abc'] as $registro) {
            $errar($this, $registro);
        }

        $errar($this, 'abc')->assertSessionHasErrors('registro');
        expect(session('errors')->first('registro'))->toContain('Muitas tentativas');
    });
});

it('o chefe libera o acesso de um jovem bloqueado pela tela de progresso', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $jovem = Jovem::first();

    foreach (range(1, config('portal.login_max_falhas')) as $i) {
        $this->post(route('portal.login'), ['registro' => '123456', 'data_nascimento' => '1999-01-01']);
    }

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire\Livewire::test(VerProgresso::class, ['record' => $jovem->getKey()])
        ->assertActionVisible('liberarAcessoPortal')
        ->callAction('liberarAcessoPortal')
        ->assertActionHidden('liberarAcessoPortal');

    $this->post(route('portal.login'), ['registro' => '123456', 'data_nascimento' => '2010-05-20'])
        ->assertRedirect(route('portal.progresso'));
});

it('o botao de liberar so aparece quando o jovem esta bloqueado', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire\Livewire::test(VerProgresso::class, ['record' => Jovem::first()->getKey()])
        ->assertActionHidden('liberarAcessoPortal');
});
