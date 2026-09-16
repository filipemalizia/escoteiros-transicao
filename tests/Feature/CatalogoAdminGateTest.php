<?php

use App\Filament\Resources\AreaDesenvolvimentoAntigas\AreaDesenvolvimentoAntigaResource;
use App\Filament\Resources\BlocoNovos\BlocoNovoResource;
use App\Filament\Resources\CompetenciaAntigas\CompetenciaAntigaResource;
use App\Filament\Resources\EixoNovos\EixoNovoResource;
use App\Filament\Resources\EquivalenciaBlocos\EquivalenciaBlocoResource;
use App\Filament\Resources\Equivalencias\EquivalenciaResource;
use App\Filament\Resources\EspecialidadeDistintivos\EspecialidadeDistintivoResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('recursos_do_catalogo', [
    'Área de Desenvolvimento Antiga' => [AreaDesenvolvimentoAntigaResource::class],
    'Competência Antiga' => [CompetenciaAntigaResource::class],
    'Eixo Novo' => [EixoNovoResource::class],
    'Bloco Novo' => [BlocoNovoResource::class],
    'Equivalência de Bloco' => [EquivalenciaBlocoResource::class],
    'Equivalência' => [EquivalenciaResource::class],
    'Especialidade/Distintivo' => [EspecialidadeDistintivoResource::class],
]);

it('impede que um usuario comum acesse a listagem do recurso do catalogo', function (string $resource) {
    $this->actingAs(User::factory()->create());

    expect($resource::canViewAny())->toBeFalse();

    $this->get($resource::getUrl('index'))->assertForbidden();
})->with('recursos_do_catalogo');

it('permite que um admin acesse a listagem do recurso do catalogo', function (string $resource) {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    expect($resource::canViewAny())->toBeTrue();

    $this->get($resource::getUrl('index'))->assertOk();
})->with('recursos_do_catalogo');

dataset('paginas_de_ferramentas', [
    'Equivalência de Bloco em Lote' => ['equivalencia-bloco-em-lote'],
    'Nova Equivalência em Lote' => ['equivalencia-em-lote'],
    'Importar Planilha' => ['importar-planilha'],
]);

it('impede que um usuario comum acesse as paginas de ferramentas', function (string $slug) {
    $this->actingAs(User::factory()->create());

    $this->get("/painel/{$slug}")->assertForbidden();
})->with('paginas_de_ferramentas');

it('permite que um admin acesse as paginas de ferramentas', function (string $slug) {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get("/painel/{$slug}")->assertOk();
})->with('paginas_de_ferramentas');
