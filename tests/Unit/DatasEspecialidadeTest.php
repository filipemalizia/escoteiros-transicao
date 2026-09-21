<?php

use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('calcula a data do nivel 1 e do nivel 2 pela ordem cronologica de conclusao dos itens', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    $especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Acampamento',
        'tipo' => 'Especialidade',
        'estrutura' => 'itens_niveis',
        'minimo_nivel_1' => 2,
        'minimo_nivel_2' => 4,
    ]);
    $especialidade->eixosNovos()->attach($eixo->id);

    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $item1 = $grupo->itens()->create(['texto' => 'Item 1']);
    $item2 = $grupo->itens()->create(['texto' => 'Item 2']);
    $item3 = $grupo->itens()->create(['texto' => 'Item 3']);
    $item4 = $grupo->itens()->create(['texto' => 'Item 4']);

    // Concluídos fora de ordem de propósito, pra provar que a data usa a
    // ordem cronológica de conclusão, não a ordem de cadastro do item.
    ProgressoEspecialidade::create(['jovem_id' => $jovem->id, 'especialidade_distintivo_item_id' => $item3->id, 'concluido' => true, 'data_conclusao' => '2026-01-05']);
    ProgressoEspecialidade::create(['jovem_id' => $jovem->id, 'especialidade_distintivo_item_id' => $item1->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);
    ProgressoEspecialidade::create(['jovem_id' => $jovem->id, 'especialidade_distintivo_item_id' => $item4->id, 'concluido' => true, 'data_conclusao' => '2026-01-15']);
    ProgressoEspecialidade::create(['jovem_id' => $jovem->id, 'especialidade_distintivo_item_id' => $item2->id, 'concluido' => true, 'data_conclusao' => '2026-01-20']);

    $dataNivel1 = $service->dataNivelEspecialidade($jovem, $especialidade, 1);
    $dataNivel2 = $service->dataNivelEspecialidade($jovem, $especialidade, 2);

    // Ordem cronológica: item3 (05/01), item1 (10/01), item4 (15/01), item2 (20/01).
    // Nível 1 (mínimo 2) = 2º concluído = 10/01. Nível 2 (mínimo 4) = 4º concluído = 20/01.
    expect($dataNivel1->format('Y-m-d'))->toBe('2026-01-10')
        ->and($dataNivel2->format('Y-m-d'))->toBe('2026-01-20');
});

it('retorna null pro nivel ainda nao atingido', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Lobinho']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);

    $especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Acampamento',
        'tipo' => 'Especialidade',
        'estrutura' => 'itens_niveis',
        'minimo_nivel_1' => 2,
        'minimo_nivel_2' => 4,
    ]);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Item 1']);

    expect($service->dataNivelEspecialidade($jovem, $especialidade, 1))->toBeNull();
});

it('calcula a data de conclusao de uma insignia pela maior data entre todos os itens dos grupos', function () {
    $service = new StatusProgressaoService;
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $ramo->id]);
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Vida ao Ar Livre']);

    $insignia = EspecialidadeDistintivo::create([
        'nome' => 'Mestre de Campo',
        'tipo' => 'Insígnia',
        'estrutura' => 'atividades_temas',
    ]);
    $insignia->eixosNovos()->attach($eixo->id);

    $grupoConhecer = $insignia->grupos()->create(['chave' => 'conhecer']);
    $itemConhecer = $grupoConhecer->itens()->create(['texto' => 'Conhecer X']);
    $grupoFazer = $insignia->grupos()->create(['chave' => 'fazer']);
    $itemFazer = $grupoFazer->itens()->create(['texto' => 'Fazer Y']);

    ProgressoEspecialidade::create(['jovem_id' => $jovem->id, 'especialidade_distintivo_item_id' => $itemConhecer->id, 'concluido' => true, 'data_conclusao' => '2026-02-01']);
    ProgressoEspecialidade::create(['jovem_id' => $jovem->id, 'especialidade_distintivo_item_id' => $itemFazer->id, 'concluido' => true, 'data_conclusao' => '2026-02-20']);

    $data = $service->dataConclusaoEspecialidade($jovem, $insignia);

    expect($data->format('Y-m-d'))->toBe('2026-02-20');
});
