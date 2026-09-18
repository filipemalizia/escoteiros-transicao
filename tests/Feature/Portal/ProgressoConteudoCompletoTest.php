<?php

use App\Livewire\Portal\EixoDetalhe;
use App\Livewire\Portal\Inicio;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EquivalenciaBloco;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $this->ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $this->itemAntigo = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item antigo vinculado ao bloco']);

    $this->eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);
    ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Item novo de teste', 'tipo_acao' => 'Obrigatória']);

    EquivalenciaBloco::create(['item_antigo_id' => $this->itemAntigo->id, 'bloco_novo_id' => $this->bloco->id]);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('mostra o bloco de reconhecimento com o resumo do programa novo na tela inicial', function () {
    Livewire::test(Inicio::class)
        ->assertSee('Reconhecimento:')
        ->assertSee('Blocos concluídos: 0 de 18');
});

it('mostra o percentual gamificado (por bloco) em vez do percentual oficial na tela inicial', function () {
    $itemObrigatoria = ItemNovo::where('bloco_id', $this->bloco->id)->first();
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $itemObrigatoria->id, 'concluido' => true, 'data_conclusao' => now()]);

    // Bloco tem 1 Obrigatória (concluída) e exige 1 Variável (nenhuma
    // concluída) - oficialmente 0% (bloco não fechou), mas o gamificado
    // conta a parte obrigatória: só esse bloco entre os 18 do ramo, média
    // (100% obrigatórias + 0% variáveis) / 2 = 50%, dividido pelos 18
    // blocos do eixo (só 1 cadastrado no teste) = 50%.
    Livewire::test(Inicio::class)
        ->assertSee('50%');
});

it('mostra uma bolinha por bloco do eixo e a contagem de concluidos na tela inicial', function () {
    $blocoConcluido = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco Concluído', 'quantidade_minima_variaveis' => 0]);
    $itemConcluido = ItemNovo::create(['bloco_id' => $blocoConcluido->id, 'codigo' => 'BC-001', 'descricao' => 'Item', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $itemConcluido->id, 'concluido' => true, 'data_conclusao' => now()]);

    $blocoPendente = BlocoNovo::create(['eixo_id' => $this->eixo->id, 'titulo' => 'Bloco Pendente', 'quantidade_minima_variaveis' => 0]);
    ItemNovo::create(['bloco_id' => $blocoPendente->id, 'codigo' => 'BP-001', 'descricao' => 'Item', 'tipo_acao' => 'Obrigatória']);

    // O eixo desse teste já tem o "Bloco 1" do beforeEach (não concluído) +
    // esses 2 novos = 3 blocos, 1 concluído.
    Livewire::test(Inicio::class)
        ->assertSee('(1 de 3)');
});

it('mostra o distintivo da etapa atual preenchendo de acordo com o progresso, nao so cor binaria', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Outro Eixo']);

    // Sênior: 1ª etapa (Escalada) precisa de 6 blocos. 3 concluídos = 50%.
    foreach (range(1, 3) as $i) {
        $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco Extra {$i}"]);
        $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "BE{$i}-001", 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
        ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => now()]);
    }

    Livewire::test(Inicio::class)
        ->assertSeeHtml('clip-path: inset(50%')
        ->assertSeeHtml('Escalada');
});

it('marca o distintivo da etapa atual com um id pra rolar a trilha ate ele automaticamente', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Outro Eixo']);

    // Sênior: bate o corte de Escalada (6) e avança pra Conquista (atual).
    foreach (range(1, 6) as $i) {
        $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco Extra {$i}"]);
        $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "BE{$i}-001", 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
        ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => now()]);
    }

    $html = Livewire::test(Inicio::class)->html();

    // Busca o rótulo "Conquista" só como texto visível entre tags (não
    // "Conquista" solto - essa palavra também aparece dentro de
    // "modalConquistasAberto"/"conquistasNovas" no wire:snapshot embutido
    // no início do HTML, o que dava falso positivo numa posição bem
    // anterior à trilha de verdade).
    preg_match('/>\s*Conquista\s*</', $html, $matches, PREG_OFFSET_CAPTURE);
    $posicaoConquista = $matches[0][1] ?? null;

    // O id só aparece uma vez (no distintivo "atual", Conquista) - se
    // aparecesse em mais de um, o `document.getElementById` do JS só acharia
    // o primeiro, quebrando a rolagem automática.
    expect(substr_count($html, 'id="trilha-etapa-atual"'))->toBe(1)
        ->and($posicaoConquista)->not->toBeNull();

    $posicaoId = strpos($html, 'id="trilha-etapa-atual"');

    expect($posicaoId)->toBeLessThan($posicaoConquista);
});

it('abre e fecha o modal de etapas mostrando quanto falta pro proximo marco', function () {
    Livewire::test(Inicio::class)
        ->assertSet('modalEtapaAberto', false)
        ->call('abrirModalEtapa')
        ->assertSet('modalEtapaAberto', true)
        ->assertSee('Faltam')
        ->call('fecharModalEtapa')
        ->assertSet('modalEtapaAberto', false);
});

it('mostra a lista de blocos do eixo com barra de progresso na cor do eixo', function () {
    $this->eixo->update(['nome' => 'Meio Ambiente']);

    Livewire::test(Inicio::class)
        ->assertSee('Bloco 1')
        ->assertSeeHtml('background-color: #4db05b');
});

it('mostra o tipo de acao do item dentro do bloco', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])
        ->assertSee('Obrigatória');
});

it('mostra os itens do programa antigo vinculados ao bloco por equivalencia de bloco', function () {
    Livewire::test(EixoDetalhe::class, ['eixo' => $this->eixo])
        ->assertSee('Item antigo vinculado ao bloco')
        ->assertSee('também contam como Ação Variável');
});
