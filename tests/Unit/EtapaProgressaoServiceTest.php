<?php

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\EtapaProgressaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new EtapaProgressaoService;
});

function criarJovem(string $ramoNome): Jovem
{
    $ramo = Ramo::firstOrCreate(['nome' => $ramoNome]);

    return Jovem::create([
        'nome' => "Jovem {$ramoNome}",
        'data_nascimento' => '2012-01-01',
        'ramo_atual_id' => $ramo->id,
    ]);
}

function criarItemAntigoComEtapa(Jovem $jovem, string $etapa, bool $concluido, ?CompetenciaAntiga $competencia = null, bool $introdutorio = false): ItemAntigo
{
    static $contador = 0;
    $contador++;

    if (! $competencia) {
        $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $jovem->ramo_atual_id, 'nome' => "Área {$contador}"]);
        $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => "Competência {$contador}"]);
    }

    $item = ItemAntigo::create([
        'competencia_id' => $competencia->id,
        'codigo' => "IT-{$contador}",
        'descricao' => "Item {$contador}",
        'etapa' => $etapa,
        'introdutorio' => $introdutorio,
    ]);

    if ($concluido) {
        ProgressoAntigo::create([
            'jovem_id' => $jovem->id,
            'item_antigo_id' => $item->id,
            'concluido' => true,
            'data_conclusao' => today(),
        ]);
    }

    return $item;
}

function marcarNItensAntigosSemEtapa(Jovem $jovem, int $total, int $concluidos): void
{
    $area = AreaDesenvolvimentoAntiga::create(['ramo_id' => $jovem->ramo_atual_id, 'nome' => 'Área única']);
    $competencia = CompetenciaAntiga::create(['area_desenvolvimento_id' => $area->id, 'descricao' => 'Competência única']);

    for ($i = 1; $i <= $total; $i++) {
        $item = ItemAntigo::create([
            'competencia_id' => $competencia->id,
            'codigo' => "SEM-{$i}",
            'descricao' => "Item {$i}",
        ]);

        if ($i <= $concluidos) {
            ProgressoAntigo::create([
                'jovem_id' => $jovem->id,
                'item_antigo_id' => $item->id,
                'concluido' => true,
                'data_conclusao' => today(),
            ]);
        }
    }
}

function criarBlocoNovoConcluido(Jovem $jovem, EixoNovo $eixo, string $codigo): BlocoNovo
{
    $bloco = BlocoNovo::create(['eixo_id' => $eixo->id, 'titulo' => "Bloco {$codigo}"]);
    $item = ItemNovo::create([
        'bloco_id' => $bloco->id,
        'codigo' => $codigo,
        'descricao' => "Item {$codigo}",
        'tipo_acao' => 'Obrigatória',
    ]);

    ProgressoNovo::create([
        'jovem_id' => $jovem->id,
        'item_novo_id' => $item->id,
        'concluido' => true,
        'data_conclusao' => today(),
    ]);

    return $bloco;
}

it('Lobinho antigo: completa o Período Introdutório mas nao o restante da piscina -> etapa atual Saltador', function () {
    $jovem = criarJovem('Lobinho');

    // itens do Período Introdutório (obrigatórios pra Pata Tenra), ambos concluídos
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', true, introdutorio: true);
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', true, introdutorio: true);

    // demais itens da piscina "Pata Tenra e Saltador", ainda pendentes
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', false);
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', false);

    expect($this->service->etapaAntigo($jovem->fresh()))->toBe('Saltador');
});

it('Lobinho antigo: Período Introdutório incompleto -> etapa atual Pata Tenra, mesmo com metade da piscina concluída', function () {
    $jovem = criarJovem('Lobinho');

    // 1 dos 2 itens introdutórios concluído (incompleto)
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', true, introdutorio: true);
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', false, introdutorio: true);

    // itens não introdutórios concluídos (dariam 50% da piscina, mas isso não basta aqui)
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', true);
    criarItemAntigoComEtapa($jovem, 'Pata Tenra e Saltador', false);

    expect($this->service->etapaAntigo($jovem->fresh()))->toBe('Pata Tenra');
});

it('Escoteiro antigo: metade da piscina Pista e Trilha concluída -> etapa atual Trilha', function () {
    $jovem = criarJovem('Escoteiro');

    criarItemAntigoComEtapa($jovem, 'Pista e Trilha', true);
    criarItemAntigoComEtapa($jovem, 'Pista e Trilha', true);
    criarItemAntigoComEtapa($jovem, 'Pista e Trilha', false);
    criarItemAntigoComEtapa($jovem, 'Pista e Trilha', false);

    expect($this->service->etapaAntigo($jovem->fresh()))->toBe('Trilha');
});

it('Escoteiro antigo: as duas piscinas 100% concluídas -> Travessia concluída', function () {
    $jovem = criarJovem('Escoteiro');

    criarItemAntigoComEtapa($jovem, 'Pista e Trilha', true);
    criarItemAntigoComEtapa($jovem, 'Pista e Trilha', true);
    criarItemAntigoComEtapa($jovem, 'Rumo e Travessia', true);
    criarItemAntigoComEtapa($jovem, 'Rumo e Travessia', true);

    expect($this->service->etapaAntigo($jovem->fresh()))->toBe('Travessia concluída');
});

it('Sênior antigo: 40% dos itens -> Conquista', function () {
    $jovem = criarJovem('Sênior');
    marcarNItensAntigosSemEtapa($jovem, 5, 2); // 2/5 = 40%

    expect($this->service->etapaAntigo($jovem->fresh()))->toBe('Conquista');
});

it('Pioneiro antigo: 60% dos itens sem as 2 flags de Cidadania -> NÃO retorna Cidadania pura', function () {
    $jovem = criarJovem('Pioneiro');
    marcarNItensAntigosSemEtapa($jovem, 5, 3); // 3/5 = 60%

    $etapa = $this->service->etapaAntigo($jovem->fresh());

    expect($etapa)->not->toBe('Cidadania')
        ->and($etapa)->toContain('pendente');
});

it('Pioneiro antigo: 60% dos itens COM as 2 flags de Cidadania -> Cidadania alcançada', function () {
    $jovem = criarJovem('Pioneiro');
    marcarNItensAntigosSemEtapa($jovem, 5, 3); // 3/5 = 60%

    $jovem->requisitosComplementares()->create([
        'chave' => 'pioneiro_antigo_projeto_em_andamento',
        'tipo' => 'booleano',
        'valor_booleano' => true,
    ]);
    $jovem->requisitosComplementares()->create([
        'chave' => 'pioneiro_antigo_plano_desenvolvimento_pessoal',
        'tipo' => 'booleano',
        'valor_booleano' => true,
    ]);

    expect($this->service->etapaAntigo($jovem->fresh()))->toBe('Cidadania');
});

it('Novo, cada um dos 4 ramos: 18 blocos concluidos + todas as flags novo -> elegivel', function (string $ramoNome, array $chaves) {
    $jovem = criarJovem($ramoNome);
    $eixo = EixoNovo::create(['ramo_id' => $jovem->ramo_atual_id, 'nome' => 'Eixo único']);

    for ($i = 1; $i <= 18; $i++) {
        criarBlocoNovoConcluido($jovem, $eixo, "B-{$i}");
    }

    foreach ($chaves as $chave) {
        $jovem->requisitosComplementares()->create([
            'chave' => $chave,
            'tipo' => 'booleano',
            'valor_booleano' => true,
        ]);
    }

    expect($this->service->elegivelReconhecimentoNovo($jovem->fresh()))->toBeTrue();
})->with([
    'Lobinho' => ['Lobinho', ['lobinho_novo_desafio_pessoal', 'lobinho_novo_avaliacao_pares']],
    'Escoteiro' => ['Escoteiro', ['escoteiro_novo_desafio_pessoal_travessia', 'escoteiro_novo_autoavaliacao', 'escoteiro_novo_avaliacao_corte_honra_escotistas']],
    'Sênior' => ['Sênior', ['senior_novo_desafio_pessoal', 'senior_novo_avaliacao_pares']],
    'Pioneiro' => ['Pioneiro', ['pioneiro_novo_desafio_pessoal', 'pioneiro_novo_avaliacao_pares']],
]);

it('Novo, qualquer ramo: 18 blocos mas falta 1 flag -> não elegível', function () {
    $jovem = criarJovem('Sênior');
    $eixo = EixoNovo::create(['ramo_id' => $jovem->ramo_atual_id, 'nome' => 'Eixo único']);

    for ($i = 1; $i <= 18; $i++) {
        criarBlocoNovoConcluido($jovem, $eixo, "B-{$i}");
    }

    // só marca 1 das 2 flags exigidas do Sênior
    $jovem->requisitosComplementares()->create([
        'chave' => 'senior_novo_desafio_pessoal',
        'tipo' => 'booleano',
        'valor_booleano' => true,
    ]);

    expect($this->service->elegivelReconhecimentoNovo($jovem->fresh()))->toBeFalse();
});

it('nomeReconhecimento retorna os nomes corretos por ramo e sistema', function () {
    $lobinho = Ramo::firstOrCreate(['nome' => 'Lobinho']);
    $senior = Ramo::firstOrCreate(['nome' => 'Sênior']);
    $pioneiro = Ramo::firstOrCreate(['nome' => 'Pioneiro']);

    expect($this->service->nomeReconhecimento($lobinho, 'antigo'))->toBe('Cruzeiro do Sul')
        ->and($this->service->nomeReconhecimento($senior, 'novo'))->toBe('Escoteiro da Pátria')
        ->and($this->service->nomeReconhecimento($pioneiro, 'novo'))->toBe('Insígnia de B-P')
        ->and($this->service->nomeReconhecimento($pioneiro, 'antigo'))->toBe('Insígnia BP');
});

it('elegivelReconhecimentoAntigo do Sênior exige 100% dos itens e as 5 chaves complementares', function () {
    $jovem = criarJovem('Sênior');
    marcarNItensAntigosSemEtapa($jovem, 4, 4); // 100%

    expect($this->service->elegivelReconhecimentoAntigo($jovem->fresh()))->toBeFalse();

    foreach ([
        'senior_antigo_cordao_dourado' => ['tipo' => 'booleano', 'valor_booleano' => true],
        'senior_antigo_insignia_interesse_especial' => ['tipo' => 'booleano', 'valor_booleano' => true],
        'senior_antigo_noites_acampadas' => ['tipo' => 'contador', 'valor_numero' => 10],
        'senior_antigo_insignia_modalidade' => ['tipo' => 'booleano', 'valor_booleano' => true],
        'senior_antigo_aprovacao_corte_honra' => ['tipo' => 'booleano', 'valor_booleano' => true],
    ] as $chave => $dados) {
        $jovem->requisitosComplementares()->create(array_merge(['chave' => $chave], $dados));
    }

    expect($this->service->elegivelReconhecimentoAntigo($jovem->fresh()))->toBeTrue();
});
