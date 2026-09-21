<?php

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use App\Models\Ramo;
use App\Services\Importacao\ImportadorEspecialidadesService;
use Database\Seeders\RamosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function catalogoDeExemplo(): array
{
    return [
        'especialidades' => [
            [
                'id' => 62,
                'nome' => 'Acampamento',
                'modelo' => 'lobinho_escoteiro',
                'eixos' => [['id' => 6, 'nome' => 'Meio Ambiente']],
                'descricao' => 'Descrição do acampamento.',
                'itens' => [
                    ['id' => 425, 'texto' => 'Montar barraca'],
                    ['id' => 426, 'texto' => 'Organizar material'],
                ],
                'regra_niveis' => 'Concluir quatro para nível 1 e oito para nível 2',
                'minimo_nivel_1' => 4,
                'minimo_nivel_2' => 8,
                'imagem_nivel_1' => 'https://exemplo.test/62-1.png',
                'imagem_nivel_2' => 'https://exemplo.test/62-2.png',
            ],
            [
                'id' => 209,
                'nome' => 'Educação Alimentar e Nutricional',
                'modelo' => 'senior_pioneiro',
                'eixos' => [['id' => 8, 'nome' => 'Saúde e Bem-estar']],
                'descricao' => 'Descrição da educação alimentar.',
                'sugestao_temas' => ['Alimentação saudável', 'Leitura de rótulos'],
                'atividades' => [
                    'conhecer' => [['id' => 4, 'texto' => 'Escolher uma temática']],
                    'fazer' => [['id' => 1, 'texto' => 'Elaborar um plano de ação']],
                    'compartilhar' => [['id' => 7, 'texto' => 'Apresentar resultados']],
                ],
                'imagem_nivel_1' => 'https://exemplo.test/209-1.png',
            ],
        ],
    ];
}

beforeEach(function () {
    $this->seed(RamosSeeder::class);
    Http::fake(['exemplo.test/*' => Http::response(base64_decode(pixelPngBase64()), 200, ['Content-Type' => 'image/png'])]);
});

it('importa uma especialidade lobinho_escoteiro criando grupo, itens, eixos e imagens', function () {
    $resumo = app(ImportadorEspecialidadesService::class)->importar(catalogoDeExemplo());

    expect($resumo->criadas)->toBe(2)
        ->and($resumo->erros)->toBeEmpty()
        ->and($resumo->eixosNovosCriados)->toBe(4) // Meio Ambiente x2 (Lobinho+Escoteiro) + Saúde x2 (Sênior+Pioneiro)
        ->and($resumo->imagensBaixadas)->toBe(3); // 62 nivel1+nivel2, 209 nivel1

    $acampamento = EspecialidadeDistintivo::where('fonte_specialty_id', 62)->first();

    expect($acampamento->estrutura)->toBe('itens_niveis')
        ->and($acampamento->minimo_nivel_1)->toBe(4)
        ->and($acampamento->eixosNovos)->toHaveCount(2)
        ->and($acampamento->eixosNovos->pluck('ramo.nome')->sort()->values()->all())->toBe(['Escoteiro', 'Lobinho']);

    $grupo = $acampamento->grupos()->where('chave', 'itens')->first();
    expect($grupo)->not->toBeNull()
        ->and($grupo->itens)->toHaveCount(2)
        ->and($grupo->itens->pluck('fonte_item_id')->all())->toBe([425, 426]);

    expect($acampamento->getFirstMediaUrl('imagem_nivel_1'))->not->toBeEmpty();
    expect($acampamento->getFirstMediaUrl('imagem_nivel_2'))->not->toBeEmpty();
});

it('importa uma especialidade senior_pioneiro com os 3 grupos de atividades', function () {
    app(ImportadorEspecialidadesService::class)->importar(catalogoDeExemplo());

    $especialidade = EspecialidadeDistintivo::where('fonte_specialty_id', 209)->first();

    expect($especialidade->estrutura)->toBe('atividades_temas')
        ->and($especialidade->sugestao_temas)->toBe(['Alimentação saudável', 'Leitura de rótulos'])
        ->and($especialidade->grupos)->toHaveCount(3);

    foreach (['conhecer', 'fazer', 'compartilhar'] as $chave) {
        $grupo = $especialidade->grupos()->where('chave', $chave)->first();
        expect($grupo)->not->toBeNull()
            ->and($grupo->itens)->toHaveCount(1)
            ->and($grupo->quantidade_minima)->toBeNull();
    }

    expect($especialidade->getFirstMedia('imagem_nivel_2'))->toBeNull();
});

it('roda de novo sem duplicar (idempotente) e pula imagens ja atualizadas', function () {
    $servico = app(ImportadorEspecialidadesService::class);
    $servico->importar(catalogoDeExemplo());

    $resumo2 = $servico->importar(catalogoDeExemplo());

    expect($resumo2->criadas)->toBe(0)
        ->and($resumo2->atualizadas)->toBe(2)
        ->and($resumo2->eixosNovosCriados)->toBe(0)
        ->and($resumo2->imagensBaixadas)->toBe(0)
        ->and($resumo2->imagensPuladas)->toBe(3)
        ->and(EspecialidadeDistintivo::count())->toBe(2)
        ->and(EixoNovo::count())->toBe(4);
});

it('rebaixa a imagem se a url mudar entre importacoes', function () {
    $servico = app(ImportadorEspecialidadesService::class);
    $servico->importar(catalogoDeExemplo());

    $catalogo = catalogoDeExemplo();
    $catalogo['especialidades'][0]['imagem_nivel_1'] = 'https://exemplo.test/62-1-nova.png';

    $resumo = $servico->importar($catalogo);

    expect($resumo->imagensBaixadas)->toBe(1); // só a que mudou
});

it('adota um EspecialidadeDistintivo legado (mesmo nome, sem fonte_specialty_id) em vez de duplicar', function () {
    // Simula o que o importador de planilha antigo já deixaria no banco:
    // um registro criado só com nome+tipo, ligado a um ItemNovo real.
    $legado = EspecialidadeDistintivo::create(['nome' => 'Acampamento', 'tipo' => 'Especialidade']);

    $ramo = Ramo::where('nome', 'Lobinho')->firstOrFail();
    $eixo = EixoNovo::create(['ramo_id' => $ramo->id, 'nome' => 'Meio Ambiente']);
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => 'Bloco X']);
    $itemLigado = ItemNovo::create([
        'bloco_id' => $bloco->id,
        'codigo' => 'X1',
        'descricao' => 'Especialidade: Acampamento',
        'tipo_acao' => 'Substitutiva',
        'especialidade_id' => $legado->id,
    ]);

    $resumo = app(ImportadorEspecialidadesService::class)->importar(catalogoDeExemplo());

    expect($resumo->adotadas)->toBe(1)
        ->and($resumo->criadas)->toBe(1) // só a 209 é realmente nova
        ->and(EspecialidadeDistintivo::where('nome', 'Acampamento')->count())->toBe(1); // não duplicou

    $legado->refresh();
    expect($legado->fonte_specialty_id)->toBe(62)
        ->and($legado->estrutura)->toBe('itens_niveis')
        ->and($itemLigado->fresh()->especialidade_id)->toBe($legado->id); // continua ligado
});

it('--dry-run nao grava nada nem baixa imagem de verdade', function () {
    $resumo = app(ImportadorEspecialidadesService::class)->importar(catalogoDeExemplo(), dryRun: true);

    expect($resumo->criadas)->toBe(2)
        ->and($resumo->imagensSimuladas)->toBe(3)
        ->and($resumo->imagensBaixadas)->toBe(0)
        ->and(EspecialidadeDistintivo::count())->toBe(0)
        ->and(EixoNovo::count())->toBe(0);

    Http::assertNothingSent();
});

it('falha com erro claro quando o ramo esperado nao existe, sem derrubar as outras especialidades', function () {
    Ramo::where('nome', 'Escoteiro')->delete();

    $resumo = app(ImportadorEspecialidadesService::class)->importar(catalogoDeExemplo());

    expect($resumo->erros)->toHaveCount(1)
        ->and($resumo->erros[0]['id'])->toBe(62)
        ->and($resumo->erros[0]['motivo'])->toContain('Escoteiro')
        ->and($resumo->criadas)->toBe(1); // 209 (senior_pioneiro) importou normalmente
});
