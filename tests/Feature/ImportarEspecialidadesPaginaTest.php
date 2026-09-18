<?php

use App\Filament\Pages\ImportarEspecialidades;
use App\Models\EspecialidadeDistintivo;
use App\Models\User;
use Database\Seeders\RamosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    (new RamosSeeder)->run();
});

function jsonEspecialidades(array $especialidades): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'especialidades.json',
        json_encode(['especialidades' => $especialidades]),
    );
}

it('importa o catalogo a partir do arquivo enviado pelo formulario', function () {
    $arquivo = jsonEspecialidades([
        [
            'id' => 1,
            'nome' => 'Acampamento',
            'modelo' => 'lobinho_escoteiro',
            'eixos' => [['id' => 6, 'nome' => 'Meio Ambiente']],
            'itens' => [['id' => 10, 'texto' => 'Montar barraca']],
        ],
    ]);

    Livewire::test(ImportarEspecialidades::class)
        ->fillForm(['arquivo' => $arquivo])
        ->call('importar')
        ->assertNotified('Importação concluída');

    expect(EspecialidadeDistintivo::where('nome', 'Acampamento')->exists())->toBeTrue();
});

it('nao grava nada no modo simulacao', function () {
    $arquivo = jsonEspecialidades([
        [
            'id' => 1,
            'nome' => 'Acampamento',
            'modelo' => 'lobinho_escoteiro',
            'eixos' => [['id' => 6, 'nome' => 'Meio Ambiente']],
            'itens' => [['id' => 10, 'texto' => 'Montar barraca']],
        ],
    ]);

    Livewire::test(ImportarEspecialidades::class)
        ->fillForm(['arquivo' => $arquivo, 'dry_run' => true])
        ->call('importar')
        ->assertNotified('Simulação concluída');

    expect(EspecialidadeDistintivo::where('nome', 'Acampamento')->exists())->toBeFalse();
});

it('avisa quando o arquivo enviado nao e um json valido', function () {
    $arquivo = UploadedFile::fake()->createWithContent('especialidades.json', 'isso nao e json');

    Livewire::test(ImportarEspecialidades::class)
        ->fillForm(['arquivo' => $arquivo])
        ->call('importar')
        ->assertNotified('Não foi possível ler o arquivo. Confirme que é um .json válido.');
});
