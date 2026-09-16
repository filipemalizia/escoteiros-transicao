<?php

use App\Filament\Pages\CriarItemPersonalizado;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\Equipe;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
    $this->equipeA = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe A']);
    $this->equipeB = Equipe::create(['ramo_id' => $this->ramo->id, 'nome' => 'Equipe B']);

    $this->jovemA1 = Jovem::create(['nome' => 'Jovem A1', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $this->equipeA->id]);
    $this->jovemA2 = Jovem::create(['nome' => 'Jovem A2', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $this->equipeA->id]);
    $this->jovemB1 = Jovem::create(['nome' => 'Jovem B1', 'data_nascimento' => '2010-01-01', 'ramo_atual_id' => $this->ramo->id, 'equipe_id' => $this->equipeB->id]);

    $eixo = EixoNovo::create(['ramo_id' => $this->ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1']);

    $this->lider = User::factory()->create();
    $this->lider->equipes()->attach($this->equipeA);
});

it('lider cria um item personalizado pra multiplos jovens da propria equipe', function () {
    $this->actingAs($this->lider);

    Livewire::test(CriarItemPersonalizado::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'bloco_novo_id' => $this->bloco->id,
            'descricao' => 'Desafio em lote',
            'jovens_ids' => [$this->jovemA1->id, $this->jovemA2->id],
        ])
        ->call('criar');

    $item = ItemPersonalizado::where('descricao', 'Desafio em lote')->first();

    expect($item)->not->toBeNull()
        ->and($item->criado_por_id)->toBe($this->lider->id)
        ->and($item->jovens->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->jovemA1->id, $this->jovemA2->id])->sort()->values()->all());
});

it('rejeita a submissao se um jovem de outra equipe for enviado no payload', function () {
    $this->actingAs($this->lider);

    Livewire::test(CriarItemPersonalizado::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'bloco_novo_id' => $this->bloco->id,
            'descricao' => 'Desafio filtrado',
            'jovens_ids' => [$this->jovemA1->id, $this->jovemB1->id],
        ])
        ->call('criar')
        ->assertHasErrors();

    expect(ItemPersonalizado::where('descricao', 'Desafio filtrado')->exists())->toBeFalse();
});

it('admin ve e consegue selecionar jovens de qualquer equipe', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(CriarItemPersonalizado::class)
        ->fillForm([
            'ramo_id' => $this->ramo->id,
            'bloco_novo_id' => $this->bloco->id,
            'descricao' => 'Desafio admin',
            'jovens_ids' => [$this->jovemA1->id, $this->jovemB1->id],
        ])
        ->call('criar');

    $item = ItemPersonalizado::where('descricao', 'Desafio admin')->first();

    expect($item->jovens->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->jovemA1->id, $this->jovemB1->id])->sort()->values()->all());
});
