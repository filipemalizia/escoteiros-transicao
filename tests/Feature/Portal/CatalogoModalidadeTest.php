<?php

use App\Livewire\Portal\Catalogo;
use App\Models\EixoNovo;
use App\Models\Equipe;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('esconde insignia de outra modalidade, mas sempre mostra as Gerais', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $equipeMar = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Tropa do Mar', 'modalidade' => 'Mar']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    $insigniaGeral = EspecialidadeDistintivo::create(['nome' => 'Mensageiros da Paz', 'tipo' => 'Insígnia']);
    $insigniaGeral->eixosNovos()->attach($eixo->id);

    $insigniaMar = EspecialidadeDistintivo::create(['nome' => 'Grumete', 'tipo' => 'Insígnia', 'modalidade' => 'Mar']);
    $insigniaMar->eixosNovos()->attach($eixo->id);

    $jovemBasico = Jovem::create([
        'nome' => 'Jovem Básico',
        'registro' => '111111',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $jovemMar = Jovem::create([
        'nome' => 'Jovem do Mar',
        'registro' => '444444',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
        'equipe_id' => $equipeMar->id,
    ]);

    $this->post(route('portal.login'), [
        'registro' => '111111',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'insignias'])
        ->assertSee('Mensageiros da Paz')
        ->assertDontSee('Grumete');

    // "Geral" aparece pra qualquer modalidade, não só pra quem não tem
    // equipe de Ar/Mar.
    $this->post(route('portal.login'), [
        'registro' => '444444',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'insignias'])
        ->assertSee('Mensageiros da Paz')
        ->assertSee('Grumete');
});

it('mostra insignia da modalidade Mar pra jovem de equipe Mar', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $equipeMar = Equipe::create(['ramo_id' => $ramo->id, 'nome' => 'Tropa do Mar', 'modalidade' => 'Mar']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    $insigniaMar = EspecialidadeDistintivo::create(['nome' => 'Grumete', 'tipo' => 'Insígnia', 'modalidade' => 'Mar']);
    $insigniaMar->eixosNovos()->attach($eixo->id);

    $jovemMar = Jovem::create([
        'nome' => 'Jovem do Mar',
        'registro' => '222222',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
        'equipe_id' => $equipeMar->id,
    ]);

    $this->post(route('portal.login'), [
        'registro' => '222222',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'insignias'])
        ->assertSee('Grumete');
});

it('nao filtra especialidades por modalidade, so insignias', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'modalidade' => 'Mar']);
    $especialidade->eixosNovos()->attach($eixo->id);

    $jovemBasico = Jovem::create([
        'nome' => 'Jovem Básico',
        'registro' => '333333',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $this->post(route('portal.login'), [
        'registro' => '333333',
        'data_nascimento' => '2010-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->assertSee('Acampamento');
});
