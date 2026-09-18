<?php

use App\Livewire\Portal\Catalogo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $this->eixoMeioAmbiente = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Meio Ambiente']);
    $this->eixoSaude = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Saúde e Bem-estar']);

    $this->acampamento = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $this->acampamento->eixosNovos()->attach($this->eixoMeioAmbiente->id);

    $this->primeirosSocorros = EspecialidadeDistintivo::create(['nome' => 'Primeiros Socorros', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $this->primeirosSocorros->eixosNovos()->attach($this->eixoSaude->id);

    $this->insignia = EspecialidadeDistintivo::create(['nome' => 'Mensageiros da Paz', 'tipo' => 'Insígnia', 'estrutura' => 'atividades_temas']);
    $this->insignia->eixosNovos()->attach($this->eixoMeioAmbiente->id);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);
});

it('mostra 404 pra um tipo de catalogo invalido', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'qualquercoisa'])->assertNotFound();
});

it('lista so as especialidades do tipo pedido, disponiveis pro ramo do jovem', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->assertSee('Acampamento')
        ->assertSee('Primeiros Socorros')
        ->assertDontSee('Mensageiros da Paz');

    Livewire::test(Catalogo::class, ['tipo' => 'insignias'])
        ->assertSee('Mensageiros da Paz')
        ->assertDontSee('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('nao mostra especialidades de outro ramo', function () {
    $outroRamo = Ramo::create(['nome' => 'Sênior']);
    $outroEixo = EixoNovo::create(['ramo_id' => $outroRamo->id, 'nome' => 'Meio Ambiente']);
    $especialidadeDeOutroRamo = EspecialidadeDistintivo::create(['nome' => 'Agricultura', 'tipo' => 'Especialidade']);
    $especialidadeDeOutroRamo->eixosNovos()->attach($outroEixo->id);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])->assertDontSee('Agricultura');
});

it('filtra por busca de nome', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'acampa')
        ->assertSee('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('filtra por eixo', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('eixoId', $this->eixoSaude->id)
        ->assertSee('Primeiros Socorros')
        ->assertDontSee('Acampamento');
});
