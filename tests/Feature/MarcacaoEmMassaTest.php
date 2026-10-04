<?php

use App\Filament\Pages\MarcacaoEmMassa;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
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
    $this->jovem1 = Jovem::create(['nome' => 'Jovem Um', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id]);
    $this->jovem2 = Jovem::create(['nome' => 'Jovem Dois', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id]);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Meio Ambiente']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco Teste', 'quantidade_minima_variaveis' => 1]);
    $this->itemNovo = ItemNovo::create(['bloco_id' => $bloco->id, 'codigo' => 'B-001', 'descricao' => 'Item novo', 'tipo_acao' => 'Obrigatória']);

    $especialidade = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade', 'estrutura' => 'atividades_temas']);
    $especialidade->eixosNovos()->attach($eixo->id);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $this->itemEspecialidade = $grupo->itens()->create(['texto' => 'Montar barraca']);
});

it('marca um item de progressao como concluido para varios jovens de uma vez', function () {
    Livewire::test(MarcacaoEmMassa::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'jovens_ids' => [$this->jovem1->id, $this->jovem2->id],
            'itens_novo_ids' => [$this->itemNovo->id],
            'data_conclusao' => '2026-09-20',
        ])
        ->call('marcar');

    foreach ([$this->jovem1, $this->jovem2] as $jovem) {
        $progresso = ProgressoNovo::where('jovem_id', $jovem->id)->where('item_novo_id', $this->itemNovo->id)->first();

        expect($progresso->concluido)->toBeTrue()
            ->and($progresso->data_conclusao->toDateString())->toBe('2026-09-20')
            ->and($progresso->registrado_por_id)->toBe($this->admin->id);
    }
});

it('marca um item de especialidade como concluido para varios jovens de uma vez', function () {
    Livewire::test(MarcacaoEmMassa::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'jovens_ids' => [$this->jovem1->id, $this->jovem2->id],
            'itens_especialidade_ids' => [$this->itemEspecialidade->id],
            'data_conclusao' => '2026-09-20',
        ])
        ->call('marcar');

    foreach ([$this->jovem1, $this->jovem2] as $jovem) {
        $progresso = ProgressoEspecialidade::where('jovem_id', $jovem->id)->where('especialidade_distintivo_item_id', $this->itemEspecialidade->id)->first();

        expect($progresso->concluido)->toBeTrue()
            ->and($progresso->data_conclusao->toDateString())->toBe('2026-09-20');
    }
});

it('marca varios itens dos dois tipos ao mesmo tempo', function () {
    Livewire::test(MarcacaoEmMassa::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'jovens_ids' => [$this->jovem1->id],
            'itens_novo_ids' => [$this->itemNovo->id],
            'itens_especialidade_ids' => [$this->itemEspecialidade->id],
            'data_conclusao' => '2026-09-20',
        ])
        ->call('marcar');

    expect(ProgressoNovo::where('jovem_id', $this->jovem1->id)->where('concluido', true)->exists())->toBeTrue()
        ->and(ProgressoEspecialidade::where('jovem_id', $this->jovem1->id)->where('concluido', true)->exists())->toBeTrue();
});

it('ignora combinacoes que ja estavam concluidas, sem sobrescrever a data original', function () {
    ProgressoNovo::create([
        'jovem_id' => $this->jovem1->id,
        'item_novo_id' => $this->itemNovo->id,
        'concluido' => true,
        'data_conclusao' => '2026-01-10',
    ]);

    Livewire::test(MarcacaoEmMassa::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'jovens_ids' => [$this->jovem1->id, $this->jovem2->id],
            'itens_novo_ids' => [$this->itemNovo->id],
            'data_conclusao' => '2026-09-20',
        ])
        ->call('marcar');

    $progressoJovem1 = ProgressoNovo::where('jovem_id', $this->jovem1->id)->where('item_novo_id', $this->itemNovo->id)->first();
    $progressoJovem2 = ProgressoNovo::where('jovem_id', $this->jovem2->id)->where('item_novo_id', $this->itemNovo->id)->first();

    expect($progressoJovem1->data_conclusao->toDateString())->toBe('2026-01-10')
        ->and($progressoJovem2->data_conclusao->toDateString())->toBe('2026-09-20');
});

it('nao faz nada se nenhum jovem ou nenhum item for selecionado', function () {
    Livewire::test(MarcacaoEmMassa::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'jovens_ids' => [$this->jovem1->id],
        ])
        ->call('marcar');

    expect(ProgressoNovo::count())->toBe(0)
        ->and(ProgressoEspecialidade::count())->toBe(0);
});

it('chefe comum nao consegue marcar progresso de um jovem fora da propria equipe, mesmo manipulando o formulario direto', function () {
    $equipeDoChefe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe do Chefe']);
    $outraEquipe = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Outra Equipe']);

    $this->jovem1->update(['equipe_id' => $equipeDoChefe->id]);
    $this->jovem2->update(['equipe_id' => $outraEquipe->id]);

    $chefe = User::factory()->create(['is_admin' => false]);
    $chefe->equipes()->attach($equipeDoChefe);
    $this->actingAs($chefe);

    // O próprio Select rejeita, na validação do form, qualquer jovem fora
    // das opções permitidas pro chefe logado (via `jovensDisponiveis()`) —
    // a ação inteira é barrada antes mesmo de rodar, nenhum jovem é marcado.
    $resultado = Livewire::test(MarcacaoEmMassa::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'jovens_ids' => [$this->jovem1->id, $this->jovem2->id],
            'itens_novo_ids' => [$this->itemNovo->id],
            'data_conclusao' => '2026-09-20',
        ])
        ->call('marcar');

    expect($resultado->errors()->isNotEmpty())->toBeTrue()
        ->and(ProgressoNovo::count())->toBe(0);
});
