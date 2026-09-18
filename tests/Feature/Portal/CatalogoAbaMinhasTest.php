<?php

use App\Livewire\Portal\Catalogo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('aba minhas mostra so especialidades com algum progresso, aba todas mostra tudo', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    $comProgresso = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $comProgresso->eixosNovos()->attach($eixo->id);
    $grupo = $comProgresso->grupos()->create(['chave' => 'itens']);
    $item = $grupo->itens()->create(['texto' => 'Montar barraca']);
    ProgressoEspecialidade::create(['jovem_id' => $jovem->id, 'especialidade_distintivo_item_id' => $item->id, 'concluido' => true, 'data_conclusao' => now()]);

    $semProgresso = EspecialidadeDistintivo::create(['nome' => 'Agricultura', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $semProgresso->eixosNovos()->attach($eixo->id);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->assertSee('Acampamento')
        ->assertSee('Agricultura')
        ->set('aba', 'minhas')
        ->assertSee('Acampamento')
        ->assertDontSee('Agricultura');
});

it('mostra a ordem do item e a data de envio em vez da observacao no modal da especialidade', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $item = $grupo->itens()->create(['texto' => 'Montar barraca', 'ordem' => 3]);

    ProgressoEspecialidade::create([
        'jovem_id' => $jovem->id,
        'especialidade_distintivo_item_id' => $item->id,
        'concluido' => false,
        'solicitado_pelo_jovem' => true,
        'solicitado_em' => '2026-01-15 10:00:00',
        'observacao_jovem' => 'Fiz no fim de semana.',
    ]);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('abrirEspecialidade', $especialidade->id)
        ->assertSee('3. Montar barraca')
        ->assertSee('Enviado em 15/01/2026')
        ->assertDontSee('Fiz no fim de semana.');
});

it('mostra a data em que o nivel foi atingido no card e no modal da especialidade', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);
    $especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Acampamento',
        'tipo' => 'Especialidade',
        'estrutura' => 'itens_niveis',
        'minimo_nivel_1' => 1,
    ]);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $item = $grupo->itens()->create(['texto' => 'Montar barraca']);

    ProgressoEspecialidade::create([
        'jovem_id' => $jovem->id,
        'especialidade_distintivo_item_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => '2026-03-10',
    ]);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->assertSee('(10/03/2026)')
        ->call('abrirEspecialidade', $especialidade->id)
        ->assertSee('Nível 1 (10/03/2026)');
});

it('tocar num item pendente da especialidade abre o modal de envio, sem precisar de botao separado', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $item = $grupo->itens()->create(['texto' => 'Montar barraca']);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->call('abrirEspecialidade', $especialidade->id)
        ->assertDontSee('Enviar para avaliação')
        ->call('abrirEnvioAvaliacao', $item->id)
        ->assertSet('enviandoAvaliacaoItemId', $item->id);
});
