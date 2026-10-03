<?php

use App\Filament\Pages\EntregaDistintivos;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EntregaDistintivo;
use App\Models\EntregaEtapa;
use App\Models\Equipe;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $this->especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'atividades_temas']);
    $this->especialidade->ramos()->attach($this->ramo->id);
    $grupo = $this->especialidade->grupos()->create(['chave' => 'itens']);
    $this->item = $grupo->itens()->create(['texto' => 'Montar barraca']);
});

/**
 * Conclui `$quantidade` blocos (1 item Obrigatório cada, sem variáveis) pro
 * jovem informado — o suficiente pra bater os cortes de etapa do Sênior
 * ([6, 'Escalada'], [12, 'Conquista'], [18, 'Azimute']).
 */
function concluirBlocos(Jovem $jovem, int $quantidade): void
{
    $eixo = EixoNovo::create(['ramo_id' => $jovem->ramo_atual_id, 'nome' => 'Eixo de Teste']);

    for ($i = 1; $i <= $quantidade; $i++) {
        $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco {$i}", 'quantidade_minima_variaveis' => 0]);
        $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "B{$i}-001", 'descricao' => "Item do bloco {$i}", 'tipo_acao' => 'Obrigatória']);

        ProgressoNovo::create([
            'jovem_id' => $jovem->id,
            'item_novo_id' => $item->id,
            'concluido' => true,
            'data_conclusao' => today(),
        ]);
    }
}

it('mostra uma especialidade concluida e ainda nao entregue na lista de pendencias', function () {
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    $pendencias = Livewire::test(EntregaDistintivos::class)->instance()->getPendencias();

    expect($pendencias)->toHaveCount(1)
        ->and($pendencias[0]['jovem']->id)->toBe($this->jovem->id)
        ->and($pendencias[0]['origem'])->toBe('especialidade')
        ->and($pendencias[0]['chave'])->toBe((string) $this->especialidade->id)
        ->and($pendencias[0]['entrega'])->toBeNull();
});

it('nao mostra uma especialidade ainda nao concluida', function () {
    $pendencias = Livewire::test(EntregaDistintivos::class)->instance()->getPendencias();

    expect($pendencias)->toHaveCount(0);
});

it('marca comprado hoje direto da lista', function () {
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    Livewire::test(EntregaDistintivos::class)->call('marcarCompradoHoje', 'especialidade', $this->jovem->id, (string) $this->especialidade->id);

    $entrega = EntregaDistintivo::where('jovem_id', $this->jovem->id)->where('especialidade_distintivo_id', $this->especialidade->id)->first();
    expect($entrega->comprado_em->isToday())->toBeTrue()
        ->and($entrega->entregue_em)->toBeNull();
});

it('marcar entregue hoje tambem garante a compra, se ainda nao tinha sido marcada', function () {
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    Livewire::test(EntregaDistintivos::class)->call('marcarEntregueHoje', 'especialidade', $this->jovem->id, (string) $this->especialidade->id);

    $entrega = EntregaDistintivo::where('jovem_id', $this->jovem->id)->where('especialidade_distintivo_id', $this->especialidade->id)->first();
    expect($entrega->comprado_em->isToday())->toBeTrue()
        ->and($entrega->entregue_em->isToday())->toBeTrue();
});

it('some da lista padrao quando ja entregue, mas aparece com "mostrar entregues" ligado', function () {
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    EntregaDistintivo::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_id' => $this->especialidade->id,
        'comprado_em' => today(),
        'entregue_em' => today(),
    ]);

    $component = Livewire::test(EntregaDistintivos::class);
    expect($component->instance()->getPendencias())->toHaveCount(0);

    $component->set('mostrarEntregues', true);
    expect($component->instance()->getPendencias())->toHaveCount(1);
});

it('edita as datas de compra e entrega pelo modal', function () {
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    Livewire::test(EntregaDistintivos::class)
        ->call('abrirEdicaoEntrega', 'especialidade', $this->jovem->id, (string) $this->especialidade->id, null, null)
        ->set('editandoCompradoEm', '2026-01-10')
        ->set('editandoEntregueEm', '2026-01-15')
        ->call('salvarEntrega')
        ->assertSet('editandoJovemId', null);

    $entrega = EntregaDistintivo::where('jovem_id', $this->jovem->id)->where('especialidade_distintivo_id', $this->especialidade->id)->first();
    expect($entrega->comprado_em->toDateString())->toBe('2026-01-10')
        ->and($entrega->entregue_em->toDateString())->toBe('2026-01-15');
});

it('usuario comum so ve jovens da propria equipe, admin ve todos', function () {
    $equipeA = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe A']);
    $equipeB = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe B']);

    $this->jovem->update(['equipe_id' => $equipeA->id]);

    $outroJovem = Jovem::create([
        'nome' => 'Outro Jovem',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
        'equipe_id' => $equipeB->id,
    ]);
    $outraEspecialidade = EspecialidadeDistintivo::create(['nome' => 'Primeiros Socorros', 'tipo' => 'Especialidade', 'estrutura' => 'atividades_temas']);
    $outraEspecialidade->ramos()->attach($this->ramo->id);
    $outroItem = $outraEspecialidade->grupos()->create(['chave' => 'itens'])->itens()->create(['texto' => 'Outro requisito']);

    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $this->item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);
    ProgressoEspecialidade::create([
        'jovem_id' => $outroJovem->id,
        'especialidade_distintivo_item_id' => $outroItem->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    $lider = User::factory()->create();
    $lider->equipes()->attach($equipeA);
    $this->actingAs($lider);

    $pendencias = Livewire::test(EntregaDistintivos::class)->instance()->getPendencias();
    $jovensNaLista = collect($pendencias)->pluck('jovem.id');

    expect($jovensNaLista)->toContain($this->jovem->id)
        ->and($jovensNaLista)->not->toContain($outroJovem->id);

    $this->actingAs($this->admin);
    $pendenciasAdmin = Livewire::test(EntregaDistintivos::class)->instance()->getPendencias();
    $jovensNaListaAdmin = collect($pendenciasAdmin)->pluck('jovem.id');

    expect($jovensNaListaAdmin)->toContain($this->jovem->id)
        ->and($jovensNaListaAdmin)->toContain($outroJovem->id);
});

it('mostra uma etapa do programa novo ja alcancada na lista de pendencias', function () {
    concluirBlocos($this->jovem, 6);

    $pendencias = Livewire::test(EntregaDistintivos::class)->instance()->getPendencias();
    $etapas = collect($pendencias)->where('origem', 'etapa');

    expect($etapas)->toHaveCount(1)
        ->and($etapas->first()['titulo'])->toBe('Escalada')
        ->and($etapas->first()['tipo_label'])->toBe('Etapa')
        ->and($etapas->first()['entrega'])->toBeNull();
});

it('nao mostra uma etapa ainda nao alcancada', function () {
    concluirBlocos($this->jovem, 3);

    $pendencias = Livewire::test(EntregaDistintivos::class)->instance()->getPendencias();

    expect(collect($pendencias)->where('origem', 'etapa'))->toHaveCount(0);
});

it('marca uma etapa como comprada e entregue', function () {
    concluirBlocos($this->jovem, 6);

    Livewire::test(EntregaDistintivos::class)->call('marcarEntregueHoje', 'etapa', $this->jovem->id, 'Escalada');

    $entrega = EntregaEtapa::where('jovem_id', $this->jovem->id)->where('etapa', 'Escalada')->first();
    expect($entrega->comprado_em->isToday())->toBeTrue()
        ->and($entrega->entregue_em->isToday())->toBeTrue();

    $pendencias = Livewire::test(EntregaDistintivos::class)->instance()->getPendencias();
    expect(collect($pendencias)->where('origem', 'etapa'))->toHaveCount(0);
});

it('edita as datas de uma etapa pelo modal', function () {
    concluirBlocos($this->jovem, 6);

    Livewire::test(EntregaDistintivos::class)
        ->call('abrirEdicaoEntrega', 'etapa', $this->jovem->id, 'Escalada', null, null)
        ->set('editandoCompradoEm', '2026-01-10')
        ->set('editandoEntregueEm', '2026-01-15')
        ->call('salvarEntrega');

    $entrega = EntregaEtapa::where('jovem_id', $this->jovem->id)->where('etapa', 'Escalada')->first();
    expect($entrega->comprado_em->toDateString())->toBe('2026-01-10')
        ->and($entrega->entregue_em->toDateString())->toBe('2026-01-15');
});
