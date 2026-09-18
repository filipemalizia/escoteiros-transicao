<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('navega do inicio ate o eixo e ate a especialidade via requisicoes http reais', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);
    BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Único']);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);
    $especialidade->eixosNovos()->attach($eixo->id);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);

    $this->get(route('portal.progresso'))->assertOk()->assertSee('Meio Ambiente');
    $this->get(route('portal.eixos.show', $eixo))->assertOk()->assertSee('Bloco Único');
    $this->get(route('portal.catalogo', 'especialidades'))->assertOk()->assertSee('Acampamento');
});
