<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    // Lobinho bate "Pata Tenra" com 4 blocos concluídos (ver
    // EtapaProgressaoService::CORTES_ETAPA_NOVO) - 3 já concluídos de
    // propósito, faltando só o 4º pro chefe completar durante o teste.
    $this->ramo = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem = Jovem::create(['nome' => 'Jovem de Teste', 'data_nascimento' => '2015-01-01', 'ramo_atual_id' => $this->ramo->id]);
    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);

    foreach (range(1, 3) as $i) {
        $bloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => "Bloco {$i}"]);
        $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "B{$i}-001", 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
        ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => now()]);
    }

    $ultimoBloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco 4']);
    $this->ultimoItem = ItemNovo::create(['bloco_id' => $ultimoBloco->id, 'codigo' => 'B4-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
});

it('dispara o popup de conquista ao completar o bloco que alcanca uma nova etapa', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $this->ultimoItem->id)
        ->assertDispatched(
            'abrir-cartao-conquista',
            tipo: 'etapa',
            titulo: 'Pata Tenra',
            jovemNome: $this->jovem->nomeExibicao(),
        );
});

it('dispara o popup de conquista com tipo bloco (nao etapa nem eixo) quando o bloco concluido nao fecha nenhuma etapa ou eixo', function () {
    // Ainda faltam 2 blocos pra bater o próximo corte (Pata Tenra já foi
    // batido nesse cenário) - completar só mais 1 deve celebrar o Bloco em
    // si, não uma Etapa nova.
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $this->ultimoItem->id);

    // Bloco 5 entra num eixo à parte, com outro bloco (Bloco 6) ainda
    // pendente - assim completar só o Bloco 5 não fecha esse eixo junto
    // (senão a celebração de Eixo, que tem prioridade, mascararia o que
    // este teste quer provar).
    $outroEixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Outro Eixo']);
    $bloco5 = BlocoNovo::create(['eixo_id' => $outroEixo->id, 'titulo' => 'Bloco 5']);
    $item5 = ItemNovo::create(['bloco_id' => $bloco5->id, 'codigo' => 'B5-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
    // Precisa ter um item pendente de verdade - um Bloco sem nenhum item
    // conta como "Concluído" à toa (nada obrigatório pendente), o que
    // fecharia o eixo sozinho e mascararia o que este teste quer provar.
    $bloco6 = BlocoNovo::create(['eixo_id' => $outroEixo->id, 'titulo' => 'Bloco 6']);
    ItemNovo::create(['bloco_id' => $bloco6->id, 'codigo' => 'B6-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $item5->id)
        ->assertDispatched('abrir-cartao-conquista', tipo: 'bloco', titulo: 'Bloco 5');
});

it('dispara o popup de conquista com tipo eixo (nao bloco) quando o ultimo bloco do eixo e concluido', function () {
    // O eixo de teste tem só o Bloco 4 (os outros 3 já foram criados/
    // concluídos direto no banco no beforeEach, sem passar pelo toggle) -
    // completá-lo fecha o eixo inteiro, e a celebração de Eixo tem
    // prioridade sobre a de Bloco na mesma ação.
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $this->ultimoItem->id)
        ->assertDispatched('abrir-cartao-conquista', tipo: 'etapa');

    // Cria um 2º eixo, só com 1 bloco, pra isolar a celebração de Eixo sem
    // que uma Etapa seja alcançada junto (evita a prioridade Etapa > Eixo
    // mascarar o que este teste quer provar).
    $eixo2 = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Espiritual']);
    $blocoUnico = BlocoNovo::create(['eixo_id' => $eixo2->id, 'titulo' => 'Bloco Único']);
    $itemUnico = ItemNovo::create(['bloco_id' => $blocoUnico->id, 'codigo' => 'BU-001', 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $itemUnico->id)
        ->assertDispatched('abrir-cartao-conquista', tipo: 'eixo', titulo: 'Eixo Espiritual');
});

it('nao dispara o popup de conquista ao desmarcar um bloco, mesmo que derrube uma etapa ja alcancada', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $this->ultimoItem->id)
        ->assertDispatched('abrir-cartao-conquista');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('toggleNovo', $this->ultimoItem->id)
        ->assertNotDispatched('abrir-cartao-conquista');
});
