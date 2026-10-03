<?php

use App\Livewire\Portal\Catalogo;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EquivalenciaEspecialidade;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
        'ramo_atual_id' => $this->ramo->id,
    ]);

    $this->eixoMeioAmbiente = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Meio Ambiente']);
    $this->eixoSaude = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Saúde e Bem-estar']);

    $this->acampamento = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $this->acampamento->eixosNovos()->attach($this->eixoMeioAmbiente->id);

    $this->primeirosSocorros = EspecialidadeDistintivo::create(['nome' => 'Primeiros Socorros', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $this->primeirosSocorros->eixosNovos()->attach($this->eixoSaude->id);

    $this->insignia = EspecialidadeDistintivo::create(['nome' => 'Mensageiros da Paz', 'tipo' => 'Insígnia', 'estrutura' => 'atividades_temas']);
    $this->insignia->eixosNovos()->attach($this->eixoMeioAmbiente->id);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2015-05-20',
    ]);
});

it('mostra 404 pra um tipo de catalogo invalido', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'qualquercoisa'])->assertNotFound();
});

it('lista so as especialidades do tipo pedido, disponiveis pro ramo do jovem', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->assertSee('Acampamento')
        ->assertSee('Primeiros Socorros')
        ->assertDontSee('Mensageiros da Paz');

    Livewire::test(Catalogo::class, ['tipo' => 'insignias'])
        ->assertSee('Mensageiros da Paz')
        ->assertDontSee('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('nao mostra especialidades de outro ramo', function () {
    $outroRamo = Ramo::create(['nome' => 'Sênior']);
    $outroEixo = EixoNovo::create(['ramo_id' => $outroRamo->id, 'nome' => 'Meio Ambiente']);
    $especialidadeDeOutroRamo = EspecialidadeDistintivo::create(['nome' => 'Agricultura', 'tipo' => 'Especialidade']);
    $especialidadeDeOutroRamo->eixosNovos()->attach($outroEixo->id);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])->assertDontSee('Agricultura');
});

it('mostra insignia ligada direto ao ramo, sem nenhum eixo', function () {
    $insigniaDoRamo = EspecialidadeDistintivo::create(['nome' => 'Insígnia da Alcateia', 'tipo' => 'Insígnia', 'estrutura' => 'atividades_temas']);
    $insigniaDoRamo->ramos()->attach($this->ramo->id);

    Livewire::test(Catalogo::class, ['tipo' => 'insignias'])->assertSee('Insígnia da Alcateia');

    $outroRamo = Ramo::create(['nome' => 'Sênior']);
    $insigniaDeOutroRamo = EspecialidadeDistintivo::create(['nome' => 'Insígnia Pioneira', 'tipo' => 'Insígnia', 'estrutura' => 'atividades_temas']);
    $insigniaDeOutroRamo->ramos()->attach($outroRamo->id);

    Livewire::test(Catalogo::class, ['tipo' => 'insignias'])->assertDontSee('Insígnia Pioneira');
});

it('filtra por busca de nome', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'acampa')
        ->assertSeeText('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('destaca o trecho buscado no nome do resultado', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'acampa')
        ->assertSee('<mark', false);
});

it('filtra por busca no texto de um requisito da especialidade', function () {
    $grupo = $this->acampamento->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Montar um acampamento com fogueira']);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'fogueira')
        ->assertSee('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('filtra por busca no item do programa novo equivalente', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Vida ao Ar Livre']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $itemNovo = ItemNovo::create([
        'bloco_id' => $bloco->id,
        'codigo' => 'VAL-010',
        'descricao' => 'Organizar uma expedição de campismo',
        'tipo_acao' => 'Substitutiva',
    ]);
    EquivalenciaEspecialidade::create([
        'especialidade_distintivo_id' => $this->acampamento->id,
        'item_novo_id' => $itemNovo->id,
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'campismo')
        ->assertSee('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('busca com varias palavras encontra mesmo quando elas estao em campos diferentes', function () {
    $grupo = $this->acampamento->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Fazer uma fogueira segura']);

    // "acampa" bate no nome, "fogueira" bate num requisito - nenhum campo
    // isolado tem as duas palavras, só o conjunto tem.
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'acampa fogueira')
        ->assertSeeText('Acampamento')
        ->assertDontSee('Primeiros Socorros');
});

it('busca com varias palavras exige todas, nao só uma', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'acampa inexistente')
        ->assertDontSee('Acampamento');
});

it('oculta requisitos que nao batem com a busca, mantendo os que batem', function () {
    $grupo = $this->acampamento->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Montar a barraca']);
    $grupo->itens()->create(['texto' => 'Fazer uma fogueira segura']);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'fogueira')
        ->call('abrirEspecialidade', $this->acampamento->id)
        ->assertSeeText('Fazer uma fogueira segura')
        ->assertDontSee('Montar a barraca');
});

it('mostra todos os requisitos quando a busca so bate no nome da especialidade', function () {
    $grupo = $this->acampamento->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Montar a barraca']);
    $grupo->itens()->create(['texto' => 'Fazer uma fogueira segura']);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'acampa')
        ->call('abrirEspecialidade', $this->acampamento->id)
        ->assertSee('Montar a barraca')
        ->assertSee('Fazer uma fogueira segura');
});

it('nao mostra itens de progressao na busca do catalogo, mesmo quando um deles bate com o termo', function () {
    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Vida ao Ar Livre']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    ItemNovo::create([
        'bloco_id' => $bloco->id,
        'codigo' => 'VAL-020',
        'descricao' => 'Organizar uma fogueira com seguranca',
        'tipo_acao' => 'Variável',
        'modalidade' => 'Básica',
    ]);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'fogueira')
        ->assertDontSee('Itens de Progressão')
        ->assertDontSee('VAL-020');
});

it('abre o modal de detalhe direto quando vem com ?abrir= na url (deep link da busca geral)', function () {
    $grupo = $this->acampamento->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Montar a barraca']);

    $this->get(route('portal.catalogo', ['tipo' => 'especialidades', 'abrir' => $this->acampamento->id]))
        ->assertOk()
        ->assertSeeText('Montar a barraca');
});

it('sem ?abrir= o modal de detalhe comeca fechado', function () {
    $grupo = $this->acampamento->grupos()->create(['chave' => 'itens']);
    $grupo->itens()->create(['texto' => 'Montar a barraca']);

    $this->get(route('portal.catalogo', ['tipo' => 'especialidades']))
        ->assertOk()
        ->assertDontSee('Montar a barraca');
});

it('busca ignora acentos: encontra sem acento um nome acentuado e vice-versa', function () {
    $educacaoAmbiental = EspecialidadeDistintivo::create(['nome' => 'Educação Ambiental', 'tipo' => 'Especialidade', 'estrutura' => 'itens_niveis']);
    $educacaoAmbiental->eixosNovos()->attach($this->eixoMeioAmbiente->id);

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'educacao ambiental')
        ->assertSeeText('Educação Ambiental')
        ->assertDontSee('Acampamento');

    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('busca', 'Educação')
        ->assertSeeText('Educação Ambiental');
});

it('filtra por eixo', function () {
    Livewire::test(Catalogo::class, ['tipo' => 'especialidades'])
        ->set('eixoId', $this->eixoSaude->id)
        ->assertSee('Primeiros Socorros')
        ->assertDontSee('Acampamento');
});
