<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * O botão "voltar" das telas de detalhe já usou url()->previous(), que se
 * mostrou pouco confiável (Livewire faz várias requisições intermediárias
 * que podem virar a "url anterior" registrada na sessão). Agora cada tela
 * de detalhe manda um destino fixo e previsível pro layout. A Especialidade/
 * Insígnia não tem mais tela própria (abre num modal/drawer de dentro do
 * Catálogo), então só Eixo e Catálogo precisam de "voltar".
 */
it('o botao voltar de cada tela de detalhe aponta pro destino certo', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);
    BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Único']);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);

    $this->get(route('portal.eixos.show', $eixo))
        ->assertSee(route('portal.progresso'), escape: false);

    $this->get(route('portal.catalogo', 'especialidades'))
        ->assertSee(route('portal.progresso'), escape: false);

    $this->get(route('portal.catalogo', 'insignias'))
        ->assertSee(route('portal.progresso'), escape: false);
});
