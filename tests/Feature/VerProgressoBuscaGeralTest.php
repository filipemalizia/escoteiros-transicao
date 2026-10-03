<?php

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
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

    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2010-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);

    $this->especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $this->especialidade->eixosNovos()->attach($eixo->id);

    $this->insignia = EspecialidadeDistintivo::create(['nome' => 'Mensageiros da Paz', 'tipo' => 'Insígnia', 'estrutura' => 'atividades_temas']);
    $this->insignia->eixosNovos()->attach($eixo->id);

    $this->itemNovo = ItemNovo::create(['bloco_id' => $this->bloco->id, 'codigo' => 'B-001', 'descricao' => 'Acender uma fogueira', 'tipo_acao' => 'Obrigatória']);
});

it('abre e fecha o modal de buscar em tudo', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->assertSet('buscaGeralAberta', false)
        ->call('abrirBuscaGeral')
        ->assertSet('buscaGeralAberta', true)
        ->assertSee('Buscar em tudo')
        ->call('fecharBuscaGeral')
        ->assertSet('buscaGeralAberta', false);
});

it('encontra especialidades, insignias e itens de progressao juntos, sem trocar de aba', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirBuscaGeral')
        ->set('buscaGeralTermo', 'acampa')
        ->assertSeeText('Acampamento');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirBuscaGeral')
        ->set('buscaGeralTermo', 'mensageiros')
        ->assertSeeText('Mensageiros da Paz');

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirBuscaGeral')
        ->set('buscaGeralTermo', 'fogueira')
        ->assertSeeText('Acender uma fogueira');
});

it('clicar num resultado de especialidade troca de aba e pre-preenche a busca daquela aba', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirResultadoEspecialidadeDaBuscaGeral', 'Especialidade', 'acampa')
        ->assertSet('abaAtiva', 'especialidades')
        ->assertSet('buscaEspecialidades', 'acampa')
        ->assertSet('buscaGeralAberta', false);

    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirResultadoEspecialidadeDaBuscaGeral', 'Insígnia', 'paz')
        ->assertSet('abaAtiva', 'insignias')
        ->assertSet('buscaInsignias', 'paz');
});

it('abrir bloco pela busca geral vai pra aba do programa novo e despacha evento pra abrir o acordeao certo', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('abrirBlocoDaBuscaGeral', $this->bloco->id)
        ->assertSet('abaAtiva', 'novo')
        ->assertSet('buscaGeralAberta', false)
        ->assertDispatched('abrir-acordeao', id: 'bloco-'.$this->bloco->id);
});

it('marca um item novo como concluido direto pela busca geral e fecha o modal', function () {
    Livewire::test(VerProgresso::class, ['record' => $this->jovem->getKey()])
        ->call('marcarConcluidoDaBuscaGeral', $this->itemNovo->id)
        ->assertSet('buscaGeralAberta', false);

    $progresso = ProgressoNovo::where('jovem_id', $this->jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();
    expect($progresso->concluido)->toBeTrue();
});
