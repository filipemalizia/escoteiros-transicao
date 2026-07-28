<?php

use App\Models\ItemAntigo;
use App\Models\Ramo;
use App\Services\Importacao\ImportadorAntigoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ImportadorAntigoService;
});

function linhasAntigo(array $linhas): Collection
{
    return collect([
        collect(['Área de Desenvolvimento', 'Competência', 'Descrição da Competência', 'Código', 'Item', 'Etapa', 'Observação/Requisito']),
        ...array_map(fn (array $linha) => collect($linha), $linhas),
    ]);
}

it('importa item do Lobinho com etapa e grava a coluna etapa', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);

    $linhas = linhasAntigo([
        ['Físico', 'Saúde', '', 'FIS-001', 'Participar de atividade física', 'Saltador', ''],
    ]);

    $resumo = $this->service->importar($linhas, $ramo);

    expect($resumo->itensCriados)->toBe(1)
        ->and($resumo->itensIgnorados)->toBe(0)
        ->and(ItemAntigo::where('codigo', 'FIS-001')->first()->etapa)->toBe('Saltador');
});

it('ignora a linha e registra erro quando a etapa esta em branco para ramo que exige etapa', function () {
    $ramo = Ramo::create(['nome' => 'Escoteiro']);

    $linhas = linhasAntigo([
        ['Físico', 'Saúde', '', 'FIS-002', 'Item sem etapa', '', ''],
    ]);

    $resumo = $this->service->importar($linhas, $ramo);

    expect($resumo->itensCriados)->toBe(0)
        ->and($resumo->itensIgnorados)->toBe(1)
        ->and($resumo->erros[0]['motivo'])->toContain('Etapa em branco')
        ->and(ItemAntigo::where('codigo', 'FIS-002')->exists())->toBeFalse();
});

it('ignora a linha e registra erro quando a etapa informada nao existe para o ramo', function () {
    $ramo = Ramo::create(['nome' => 'Lobinho']);

    $linhas = linhasAntigo([
        ['Físico', 'Saúde', '', 'FIS-003', 'Item com etapa invalida', 'Travessia', ''],
    ]);

    $resumo = $this->service->importar($linhas, $ramo);

    expect($resumo->itensCriados)->toBe(0)
        ->and($resumo->itensIgnorados)->toBe(1)
        ->and($resumo->erros[0]['motivo'])->toContain('não reconhecida');
});

it('nao exige etapa para ramos que nao usam esse conceito (Sênior/Pioneiro)', function () {
    $ramo = Ramo::create(['nome' => 'Sênior']);

    $linhas = linhasAntigo([
        ['Físico', 'Saúde', '', 'FIS-004', 'Item do Sênior', '', ''],
    ]);

    $resumo = $this->service->importar($linhas, $ramo);

    expect($resumo->itensCriados)->toBe(1)
        ->and($resumo->itensIgnorados)->toBe(0)
        ->and(ItemAntigo::where('codigo', 'FIS-004')->first()->etapa)->toBeNull();
});
