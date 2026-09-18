<?php

use App\Livewire\Portal\Timeline;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
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

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('mostra um bloco concluido e um nivel de especialidade atingido na ordem certa por data', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Concluído']);
    $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B1-001', 'descricao' => 'Obg 1', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $item->id, 'concluido' => true, 'data_conclusao' => '2026-01-10']);

    $especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Acampamento',
        'tipo' => 'Especialidade',
        'estrutura' => 'itens_niveis',
        'minimo_nivel_1' => 1,
    ]);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $itemEspecialidade = $grupo->itens()->create(['texto' => 'Montar barraca']);
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $itemEspecialidade->id,
        'concluido' => true,
        'data_conclusao' => '2026-02-20',
    ]);

    $test = Livewire::test(Timeline::class)
        ->assertSee('Bloco Concluído')
        ->assertSee('Acampamento')
        ->assertSeeInOrder(['Acampamento', 'Bloco Concluído']);

    $test->assertSee('20/02/2026')->assertSee('10/01/2026');
});

it('mostra uma insignia concluida', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Vida ao Ar Livre']);
    $insignia = EspecialidadeDistintivo::create([
        'nome' => 'Mestre de Campo',
        'tipo' => 'Insígnia',
        'estrutura' => 'atividades_temas',
    ]);
    $insignia->eixosNovos()->attach($eixo->id);
    $grupo = $insignia->grupos()->create(['chave' => 'conhecer']);
    $item = $grupo->itens()->create(['texto' => 'Conhecer X']);
    ProgressoEspecialidade::create([
        'jovem_id' => $this->jovem->id,
        'especialidade_distintivo_item_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => '2026-03-01',
    ]);

    Livewire::test(Timeline::class)
        ->assertSee('Mestre de Campo')
        ->assertSee('Concluída');
});

it('mostra mensagem quando nao ha nenhuma conquista ainda', function () {
    Livewire::test(Timeline::class)
        ->assertSee('Nenhuma conquista ainda.');
});

it('carrega mais eventos ao clicar no botao, respeitando o limite inicial', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);

    foreach (range(1, 32) as $i) {
        $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco {$i}"]);
        $item = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => "B{$i}-001", 'descricao' => 'Obg', 'tipo_acao' => 'Obrigatória']);
        ProgressoNovo::create([
            'jovem_id' => $this->jovem->id,
            'item_novo_id' => $item->id,
            'concluido' => true,
            'data_conclusao' => now()->subDays($i),
        ]);
    }

    Livewire::test(Timeline::class)
        ->assertSee('Bloco 1')
        ->assertDontSee('Bloco 32')
        ->assertSee('Carregar mais')
        ->call('carregarMais')
        ->assertSee('Bloco 32');
});
