<?php

use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new StatusProgressaoService;
    $this->ramo = Ramo::create(['nome' => 'Lobinho']);
    $this->jovem = Jovem::create([
        'nome' => 'Jovem de Teste',
        'data_nascimento' => '2015-01-01',
        'ramo_atual_id' => $this->ramo->id,
    ]);
});

function marcarItemEspecialidadeConcluido(Jovem $jovem, int $itemId, StatusProgressaoService $service): void
{
    ProgressoEspecialidade::create([
        'jovem_id' => $jovem->id,
        'especialidade_distintivo_item_id' => $itemId,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    $service->limparCache();
}

it('calcula nivel_atingido pra estrutura itens_niveis conforme minimo_nivel_1/2', function () {
    $especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Acampamento',
        'tipo' => 'Especialidade',
        'estrutura' => 'itens_niveis',
        'minimo_nivel_1' => 4,
        'minimo_nivel_2' => 8,
    ]);
    $grupo = $especialidade->grupos()->create(['chave' => 'itens']);
    $itens = collect(range(1, 8))->map(fn ($i) => $grupo->itens()->create(['texto' => "Item {$i}"]));

    $status = $this->service->statusEspecialidade($this->jovem, $especialidade->fresh('grupos.itens'));
    expect($status['nivel_atingido'])->toBe(0)
        ->and($status['status'])->toBe('Pendente');

    foreach ($itens->take(3) as $item) {
        marcarItemEspecialidadeConcluido($this->jovem, $item->id, $this->service);
    }
    $status = $this->service->statusEspecialidade($this->jovem, $especialidade->fresh('grupos.itens'));
    expect($status['nivel_atingido'])->toBe(0)
        ->and($status['status'])->toBe('Parcial')
        ->and($status['itens_concluidos'])->toBe(3);

    marcarItemEspecialidadeConcluido($this->jovem, $itens[3]->id, $this->service);
    $status = $this->service->statusEspecialidade($this->jovem, $especialidade->fresh('grupos.itens'));
    expect($status['nivel_atingido'])->toBe(1)
        ->and($status['status'])->toBe('Concluído');

    foreach ($itens->skip(4) as $item) {
        marcarItemEspecialidadeConcluido($this->jovem, $item->id, $this->service);
    }
    $status = $this->service->statusEspecialidade($this->jovem, $especialidade->fresh('grupos.itens'));
    expect($status['nivel_atingido'])->toBe(2)
        ->and($status['itens_concluidos'])->toBe(8);
});

it('estrutura atividades_temas so conta como Concluído quando TODOS os grupos estao satisfeitos', function () {
    $especialidade = EspecialidadeDistintivo::create([
        'nome' => 'Educação Alimentar',
        'tipo' => 'Especialidade',
        'estrutura' => 'atividades_temas',
    ]);

    $conhecer = $especialidade->grupos()->create(['chave' => 'conhecer']);
    $itemConhecer = $conhecer->itens()->create(['texto' => 'Escolher tema']);

    $fazer = $especialidade->grupos()->create(['chave' => 'fazer']);
    $itemFazer = $fazer->itens()->create(['texto' => 'Elaborar plano']);

    $status = $this->service->statusEspecialidade($this->jovem, $especialidade->fresh('grupos.itens'));
    expect($status['status'])->toBe('Pendente')
        ->and($status['nivel_atingido'])->toBeNull();

    marcarItemEspecialidadeConcluido($this->jovem, $itemConhecer->id, $this->service);
    $status = $this->service->statusEspecialidade($this->jovem, $especialidade->fresh('grupos.itens'));
    expect($status['status'])->toBe('Parcial');

    marcarItemEspecialidadeConcluido($this->jovem, $itemFazer->id, $this->service);
    $status = $this->service->statusEspecialidade($this->jovem, $especialidade->fresh('grupos.itens'));
    expect($status['status'])->toBe('Concluído');
});

it('quantidade_minima null exige todos os itens do grupo; numero exige so aquele minimo', function () {
    $especialidade = EspecialidadeDistintivo::create(['nome' => 'X', 'tipo' => 'Especialidade', 'estrutura' => 'atividades_temas']);

    $grupoTodos = $especialidade->grupos()->create(['chave' => 'conhecer', 'quantidade_minima' => null]);
    $itensTodos = collect(range(1, 3))->map(fn ($i) => $grupoTodos->itens()->create(['texto' => "Item {$i}"]));

    $grupoMinimo = $especialidade->grupos()->create(['chave' => 'fazer', 'quantidade_minima' => 1]);
    $itensMinimo = collect(range(1, 3))->map(fn ($i) => $grupoMinimo->itens()->create(['texto' => "Item {$i}"]));

    marcarItemEspecialidadeConcluido($this->jovem, $itensTodos[0]->id, $this->service);
    marcarItemEspecialidadeConcluido($this->jovem, $itensMinimo[0]->id, $this->service);

    $statusTodos = $this->service->statusGrupoEspecialidade($this->jovem, $grupoTodos->fresh('itens'));
    $statusMinimo = $this->service->statusGrupoEspecialidade($this->jovem, $grupoMinimo->fresh('itens'));

    expect($statusTodos['satisfeito'])->toBeFalse() // só 1 de 3, precisa de todos
        ->and($statusMinimo['satisfeito'])->toBeTrue(); // 1 de 3, mínimo é 1
});
