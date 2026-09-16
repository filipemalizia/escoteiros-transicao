<?php

use App\Livewire\Portal\Progresso;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoPersonalizado;
use App\Models\Ramo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $this->outroJovem = Jovem::create([
        'nome' => 'Outro Jovem',
        'registro' => '999999',
        'data_nascimento' => '2010-05-20',
        'ramo_atual_id' => $ramo->id,
    ]);

    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Eixo Corporal']);
    $this->bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco 1', 'quantidade_minima_variaveis' => 1]);

    $this->item = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Desafio especial']);
    $this->item->jovens()->attach($this->jovem->id);

    $this->post(route('portal.login'), [
        'registro' => '123456',
        'data_nascimento' => '2010-05-20',
    ]);
});

it('mostra o item personalizado do jovem no portal', function () {
    Livewire::test(Progresso::class)
        ->assertSee('Desafio especial')
        ->assertSee('Personalizado');
});

it('jovem solicita avaliacao de um item personalizado', function () {
    Livewire::test(Progresso::class)->call('solicitarItemPersonalizado', $this->item->id);

    $progresso = ProgressoPersonalizado::where('item_personalizado_id', $this->item->id)->first();

    expect($progresso->solicitado_pelo_jovem)->toBeTrue()
        ->and($progresso->concluido)->toBeFalse();
});

it('jovem nao consegue solicitar avaliacao de um item personalizado de outro jovem', function () {
    $itemDeOutroJovem = ItemPersonalizado::create(['bloco_novo_id' => $this->bloco->id, 'descricao' => 'Não é seu']);
    $itemDeOutroJovem->jovens()->attach($this->outroJovem->id);

    Livewire::test(Progresso::class)
        ->call('solicitarItemPersonalizado', $itemDeOutroJovem->id)
        ->assertForbidden();

    expect(ProgressoPersonalizado::where('item_personalizado_id', $itemDeOutroJovem->id)->exists())->toBeFalse();
});
