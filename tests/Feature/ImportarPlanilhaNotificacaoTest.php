<?php

use App\Filament\Pages\ImportarPlanilha;
use App\Models\Ramo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->ramo = Ramo::create(['nome' => 'Sênior']);
});

function csvAntigo(string $conteudo): UploadedFile
{
    $cabecalho = 'Área de Desenvolvimento;Competência;Descrição da Competência;Código;Item;Etapa;Observação/Requisito';

    return UploadedFile::fake()->createWithContent('planilha.csv', $cabecalho."\n".$conteudo);
}

it('mostra notificação de sucesso quando todas as linhas sao importadas', function () {
    $arquivo = csvAntigo('Físico;Saúde;;FIS-100;Item 1;;');

    Livewire::test(ImportarPlanilha::class)
        ->fillForm([
            'sistema' => 'antigo',
            'ramo_id' => $this->ramo->id,
            'arquivo' => $arquivo,
        ])
        ->call('importar')
        ->assertNotified('Importação concluída');
});

it('mostra notificação de alerta quando algumas linhas sao ignoradas', function () {
    $arquivo = csvAntigo("Físico;Saúde;;FIS-100;Item 1;;\nFísico;;;FIS-101;Item 2;;");

    Livewire::test(ImportarPlanilha::class)
        ->fillForm([
            'sistema' => 'antigo',
            'ramo_id' => $this->ramo->id,
            'arquivo' => $arquivo,
        ])
        ->call('importar')
        ->assertNotified('Importação concluída com pendências — confira as linhas ignoradas abaixo');
});

it('mostra notificação de erro quando nenhuma linha e importada', function () {
    $arquivo = csvAntigo('Físico;;;FIS-101;Item 2;;');

    Livewire::test(ImportarPlanilha::class)
        ->fillForm([
            'sistema' => 'antigo',
            'ramo_id' => $this->ramo->id,
            'arquivo' => $arquivo,
        ])
        ->call('importar')
        ->assertNotified('Nenhum item foi importado');
});
