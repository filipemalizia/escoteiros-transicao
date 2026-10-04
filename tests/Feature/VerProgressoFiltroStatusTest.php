<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $ramo->id,
    ]);

    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $ramo->id, 'nome' => 'Físico']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Saúde']);
    $this->itemAntigoPendente = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-001', 'descricao' => 'Item antigo pendente']);
    $this->itemAntigoConcluido = ItemAntigo::create(['competencia_id' => $competencia->id, 'codigo' => 'FIS-002', 'descricao' => 'Item antigo concluido']);
    ProgressoAntigo::create(['jovem_id' => $this->jovem->id, 'item_antigo_id' => $this->itemAntigoConcluido->id, 'concluido' => true, 'data_conclusao' => today()]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $this->itemNovoPendente = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-001', 'descricao' => 'Item novo pendente', 'tipo_acao' => 'Obrigatória']);
    $this->itemNovoConcluido = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-002', 'descricao' => 'Item novo concluido', 'tipo_acao' => 'Obrigatória']);
    ProgressoNovo::create(['jovem_id' => $this->jovem->id, 'item_novo_id' => $this->itemNovoConcluido->id, 'concluido' => true, 'data_conclusao' => today()]);

    $itemPersonalizado = ItemPersonalizado::create(['bloco_novo_id' => $bloco->id, 'descricao' => 'Item personalizado concluido']);
    $itemPersonalizado->jovens()->attach($this->jovem->id);
    ProgressoPersonalizado::create(['jovem_id' => $this->jovem->id, 'item_personalizado_id' => $itemPersonalizado->id, 'concluido' => true, 'data_conclusao' => today()]);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'atividades_temas']);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $this->itemEspecialidadePendente = $grupo->itens()->create(['texto' => 'Requisito pendente']);
    $this->itemEspecialidadeConcluido = $grupo->itens()->create(['texto' => 'Requisito concluido']);
    ProgressoEspecialidade::create(['jovem_id' => $this->jovem->id, 'especialidade_distintivo_item_id' => $this->itemEspecialidadeConcluido->id, 'concluido' => true, 'data_conclusao' => today()]);
});

it('mostra todos os itens por padrao, em todas as abas', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSee('Item novo pendente')
        ->assertSee('Item novo concluido')
        ->set('abaAtiva', 'especialidades')
        ->assertSee('Requisito pendente')
        ->assertSee('Requisito concluido')
        ->set('abaAtiva', 'antigo')
        ->assertSee('Item antigo pendente')
        ->assertSee('Item antigo concluido');
});

it('filtro pendente esconde os itens concluidos do programa novo e mostra os pendentes', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('filtroStatusItens', 'pendente')
        ->assertSee('Item novo pendente')
        ->assertDontSee('Item novo concluido');
});

it('filtro concluido esconde os itens pendentes do programa novo e mostra os concluidos', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('filtroStatusItens', 'concluido')
        ->assertDontSee('Item novo pendente')
        ->assertSee('Item novo concluido');
});

it('filtro pendente/concluido tambem se aplica aos itens personalizados do programa novo', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('filtroStatusItens', 'pendente')
        ->assertDontSee('Item personalizado concluido');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('filtroStatusItens', 'concluido')
        ->assertSee('Item personalizado concluido');
});

it('filtro pendente/concluido se aplica aos requisitos de especialidade', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'especialidades')
        ->set('filtroStatusItens', 'pendente')
        ->assertSee('Requisito pendente')
        ->assertDontSee('Requisito concluido');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'especialidades')
        ->set('filtroStatusItens', 'concluido')
        ->assertDontSee('Requisito pendente')
        ->assertSee('Requisito concluido');
});

it('filtro pendente/concluido se aplica aos itens do programa antigo', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'antigo')
        ->set('filtroStatusItens', 'pendente')
        ->assertSee('Item antigo pendente')
        ->assertDontSee('Item antigo concluido');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->set('abaAtiva', 'antigo')
        ->set('filtroStatusItens', 'concluido')
        ->assertDontSee('Item antigo pendente')
        ->assertSee('Item antigo concluido');
});
