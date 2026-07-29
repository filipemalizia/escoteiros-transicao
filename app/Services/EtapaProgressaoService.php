<?php

namespace App\Services;

use App\Models\ItemAntigo;
use App\Models\Jovem;
use App\Models\Ramo;

class EtapaProgressaoService
{
    public function __construct(
        private readonly EquivalenciaCreditoService $creditoService = new EquivalenciaCreditoService,
        private readonly StatusProgressaoService $statusService = new StatusProgressaoService,
    ) {}

    /**
     * Itens do programa antigo, por ramo, são agrupados em "piscinas" (coluna
     * `etapa` grava o nome da piscina, não da etapa individual) — cada
     * piscina reúne os itens de 2 etapas seguidas, indiferentes entre si.
     * Atingir metade da piscina = 1ª etapa; 100% = 2ª etapa. As piscinas são
     * independentes entre si (não é preciso terminar a 1ª pra progredir na
     * 2ª), mas a "etapa atual" exibida ainda percorre as piscinas na ordem
     * abaixo e mostra a primeira que não estiver 100% concluída.
     *
     * No Lobinho, a 1ª piscina tem um "Período Introdutório": um subconjunto
     * fixo de itens marcados como `introdutorio = true` que precisa estar
     * 100% concluído (não uma fração qualquer) pra valer a 1ª etapa (Pata
     * Tenra). As demais piscinas usam só a fração de 50%/100%.
     *
     * @return array<int, array{pool: string, meio: string, cheio: string, usa_introdutorio: bool}>
     */
    protected const POOLS_ANTIGO_POR_ITEM = [
        'Lobinho' => [
            ['pool' => 'Pata Tenra e Saltador', 'meio' => 'Pata Tenra', 'cheio' => 'Saltador', 'usa_introdutorio' => true],
            ['pool' => 'Rastreador e Caçador', 'meio' => 'Rastreador', 'cheio' => 'Caçador', 'usa_introdutorio' => false],
        ],
        'Escoteiro' => [
            ['pool' => 'Pista e Trilha', 'meio' => 'Pista', 'cheio' => 'Trilha', 'usa_introdutorio' => false],
            ['pool' => 'Rumo e Travessia', 'meio' => 'Rumo', 'cheio' => 'Travessia', 'usa_introdutorio' => false],
        ],
    ];

    /**
     * Cortes de etapa do programa novo, por quantidade de Blocos concluídos (sempre 18 no total).
     * Cada par é [quantidade_maxima_inclusive, nome_da_etapa].
     */
    protected const CORTES_ETAPA_NOVO = [
        'Lobinho' => [[3, 'Pata Tenra'], [7, 'Saltador'], [12, 'Rastreador'], [17, 'Caçador'], [18, 'Caçador concluído']],
        'Escoteiro' => [[3, 'Pistas'], [7, 'Trilha'], [12, 'Rumo'], [17, 'Travessia'], [18, 'Travessia concluída']],
        'Sênior' => [[5, 'Escalada'], [11, 'Conquista'], [17, 'Azimute'], [18, 'Azimute concluída']],
        'Pioneiro' => [[5, 'Descoberta'], [11, 'Destino'], [17, 'Horizonte'], [18, 'Horizonte concluída']],
    ];

    protected const NOMES_RECONHECIMENTO = [
        'antigo' => [
            'Lobinho' => 'Cruzeiro do Sul',
            'Escoteiro' => 'Lis de Ouro',
            'Sênior' => 'Distintivo de Escoteiro da Pátria',
            'Pioneiro' => 'Insígnia BP',
        ],
        'novo' => [
            'Lobinho' => 'Cruzeiro do Sul',
            'Escoteiro' => 'Lis de Ouro',
            'Sênior' => 'Escoteiro da Pátria',
            'Pioneiro' => 'Insígnia de B-P',
        ],
    ];

    /**
     * As 2 chaves do Pioneiro que valem pra Insígnia BP (diferentes das 2 da etapa Cidadania).
     */
    protected const PIONEIRO_CHAVES_INSIGNIA_BP = [
        'pioneiro_antigo_projeto_relevante_executado',
        'pioneiro_antigo_revisao_plano_pessoal',
    ];

    public function nomeReconhecimento(Ramo $ramo, string $sistema): string
    {
        return self::NOMES_RECONHECIMENTO[$sistema][$ramo->nome] ?? 'Reconhecimento';
    }

    /**
     * Etapas válidas (em ordem) do programa antigo por item, para o ramo informado.
     * Vazio para ramos que não usam esse conceito (Sênior/Pioneiro).
     *
     * @return array<int, string>
     */
    public static function etapasAntigoPorRamo(string $ramoNome): array
    {
        return array_column(self::POOLS_ANTIGO_POR_ITEM[$ramoNome] ?? [], 'pool');
    }

    /**
     * Se a piscina (valor da coluna `etapa`) exige que um subconjunto fixo de
     * itens (o "Período Introdutório") esteja 100% concluído pra valer a
     * 1ª etapa da piscina, em vez de uma fração qualquer de 50%.
     */
    public static function poolUsaIntrodutorio(string $ramoNome, ?string $pool): bool
    {
        foreach (self::POOLS_ANTIGO_POR_ITEM[$ramoNome] ?? [] as $definicao) {
            if ($definicao['pool'] === $pool) {
                return $definicao['usa_introdutorio'];
            }
        }

        return false;
    }

    // ------------------------------------------------------------------
    // Catálogo de chaves complementares (usado pelo form, pela elegibilidade
    // e pela lista de pendências — fonte única de verdade)
    // ------------------------------------------------------------------

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int}>
     */
    public function chavesComplementaresAntigo(string $ramoNome): array
    {
        return match ($ramoNome) {
            'Lobinho' => [
                ['chave' => 'lobinho_antigo_acampamentos', 'tipo' => 'contador', 'label' => 'Acampamentos participados', 'meta' => 3],
                ['chave' => 'lobinho_antigo_especialidades_variadas', 'tipo' => 'booleano', 'label' => '5+ especialidades de 3 ramos de conhecimento diferentes'],
                ['chave' => 'lobinho_antigo_insignia_interesse_especial', 'tipo' => 'booleano', 'label' => 'Insígnia de Interesse Especial'],
                ['chave' => 'lobinho_antigo_recomendacao_velhos_lobos', 'tipo' => 'booleano', 'label' => 'Recomendação dos Velhos Lobos'],
            ],
            'Escoteiro' => [
                ['chave' => 'escoteiro_antigo_cordao_vermelho_branco', 'tipo' => 'booleano', 'label' => 'Cordão Vermelho e Branco'],
                ['chave' => 'escoteiro_antigo_insignia_interesse_especial', 'tipo' => 'booleano', 'label' => 'Insígnia de Interesse Especial'],
                ['chave' => 'escoteiro_antigo_noites_acampamento', 'tipo' => 'contador', 'label' => 'Noites de acampamento', 'meta' => 10],
                ['chave' => 'escoteiro_antigo_insignia_modalidade', 'tipo' => 'booleano', 'label' => 'Insígnia de Modalidade'],
                ['chave' => 'escoteiro_antigo_recomendacao_corte_honra', 'tipo' => 'booleano', 'label' => 'Recomendação da Corte de Honra'],
            ],
            'Sênior' => [
                ['chave' => 'senior_antigo_cordao_dourado', 'tipo' => 'booleano', 'label' => 'Cordão Dourado'],
                ['chave' => 'senior_antigo_insignia_interesse_especial', 'tipo' => 'booleano', 'label' => 'Insígnia de Interesse Especial'],
                ['chave' => 'senior_antigo_noites_acampadas', 'tipo' => 'contador', 'label' => 'Noites acampadas', 'meta' => 10],
                ['chave' => 'senior_antigo_insignia_modalidade', 'tipo' => 'booleano', 'label' => 'Insígnia de Modalidade'],
                ['chave' => 'senior_antigo_aprovacao_corte_honra', 'tipo' => 'booleano', 'label' => 'Aprovação da Corte de Honra'],
            ],
            'Pioneiro' => [
                ['chave' => 'pioneiro_antigo_projeto_em_andamento', 'tipo' => 'booleano', 'label' => 'Projeto em andamento (etapa Cidadania)'],
                ['chave' => 'pioneiro_antigo_plano_desenvolvimento_pessoal', 'tipo' => 'booleano', 'label' => 'Plano de desenvolvimento pessoal (etapa Cidadania)'],
                ['chave' => 'pioneiro_antigo_projeto_relevante_executado', 'tipo' => 'booleano', 'label' => 'Projeto relevante executado (Insígnia BP)'],
                ['chave' => 'pioneiro_antigo_revisao_plano_pessoal', 'tipo' => 'booleano', 'label' => 'Revisão do plano de desenvolvimento pessoal (Insígnia BP)'],
            ],
            default => [],
        };
    }

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int}>
     */
    public function chavesComplementaresNovo(string $ramoNome): array
    {
        return match ($ramoNome) {
            'Lobinho' => [
                ['chave' => 'lobinho_novo_desafio_pessoal', 'tipo' => 'booleano', 'label' => 'Desafio pessoal'],
                ['chave' => 'lobinho_novo_avaliacao_pares', 'tipo' => 'booleano', 'label' => 'Avaliação dos pares'],
            ],
            'Escoteiro' => [
                ['chave' => 'escoteiro_novo_desafio_pessoal_travessia', 'tipo' => 'booleano', 'label' => 'Desafio pessoal da Travessia'],
                ['chave' => 'escoteiro_novo_autoavaliacao', 'tipo' => 'booleano', 'label' => 'Autoavaliação'],
                ['chave' => 'escoteiro_novo_avaliacao_corte_honra_escotistas', 'tipo' => 'booleano', 'label' => 'Avaliação da Corte de Honra e dos escotistas'],
            ],
            'Sênior' => [
                ['chave' => 'senior_novo_desafio_pessoal', 'tipo' => 'booleano', 'label' => 'Desafio pessoal'],
                ['chave' => 'senior_novo_avaliacao_pares', 'tipo' => 'booleano', 'label' => 'Avaliação dos pares'],
            ],
            'Pioneiro' => [
                ['chave' => 'pioneiro_novo_desafio_pessoal', 'tipo' => 'booleano', 'label' => 'Desafio pessoal'],
                ['chave' => 'pioneiro_novo_avaliacao_pares', 'tipo' => 'booleano', 'label' => 'Avaliação dos pares'],
            ],
            default => [],
        };
    }

    /**
     * @param  array{chave: string, tipo: string, label: string, meta?: int}  $chaveDef
     */
    protected function chaveSatisfeita(Jovem $jovem, array $chaveDef): bool
    {
        if ($chaveDef['tipo'] === 'contador') {
            return $jovem->requisitoNumero($chaveDef['chave']) >= ($chaveDef['meta'] ?? 0);
        }

        return $jovem->requisitoBool($chaveDef['chave']);
    }

    /**
     * @return array<int, string>
     */
    public function pendenciasComplementaresAntigo(Jovem $jovem): array
    {
        $ramoNome = $jovem->ramoAtual->nome;
        $chaves = $this->chavesComplementaresAntigo($ramoNome);

        if ($ramoNome === 'Pioneiro') {
            $chaves = array_values(array_filter(
                $chaves,
                fn (array $chaveDef) => in_array($chaveDef['chave'], self::PIONEIRO_CHAVES_INSIGNIA_BP, true)
            ));
        }

        return array_values(array_map(
            fn (array $chaveDef) => $chaveDef['label'],
            array_filter($chaves, fn (array $chaveDef) => ! $this->chaveSatisfeita($jovem, $chaveDef))
        ));
    }

    /**
     * @return array<int, string>
     */
    public function pendenciasComplementaresNovo(Jovem $jovem): array
    {
        $chaves = $this->chavesComplementaresNovo($jovem->ramoAtual->nome);

        return array_values(array_map(
            fn (array $chaveDef) => $chaveDef['label'],
            array_filter($chaves, fn (array $chaveDef) => ! $this->chaveSatisfeita($jovem, $chaveDef))
        ));
    }

    // ------------------------------------------------------------------
    // Fração de itens antigos concluídos (usada por Sênior/Pioneiro)
    // ------------------------------------------------------------------

    /**
     * @return array{total: int, concluidos: int, fracao: float}
     */
    protected function fracaoItensAntigos(Jovem $jovem): array
    {
        $itens = ItemAntigo::query()
            ->whereHas('competencia.areaDesenvolvimento', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->get();

        $total = $itens->count();
        $concluidos = $itens->filter(
            fn (ItemAntigo $item) => $this->creditoService->itemAntigoConcluido($jovem, $item)
        )->count();

        return [
            'total' => $total,
            'concluidos' => $concluidos,
            'fracao' => $total > 0 ? $concluidos / $total : 0.0,
        ];
    }

    // ------------------------------------------------------------------
    // etapaAntigo
    // ------------------------------------------------------------------

    public function etapaAntigo(Jovem $jovem): string
    {
        $ramoNome = $jovem->ramoAtual->nome;

        return match ($ramoNome) {
            'Lobinho', 'Escoteiro' => $this->etapaAntigoPorItem($jovem, $ramoNome),
            'Sênior' => $this->etapaAntigoSenior($jovem),
            'Pioneiro' => $this->etapaAntigoPioneiro($jovem),
            default => 'Indefinida',
        };
    }

    protected function etapaAntigoPorItem(Jovem $jovem, string $ramoNome): string
    {
        $pools = self::POOLS_ANTIGO_POR_ITEM[$ramoNome];

        foreach ($pools as $definicao) {
            $status = $this->statusPoolAntigoPorItem($jovem, $definicao);

            // Piscina 100% concluída: passa pra próxima (piscinas são
            // independentes na apuração, mas a etapa exibida ainda percorre
            // na ordem nominal e mostra a primeira piscina não concluída).
            if ($status === 2) {
                continue;
            }

            return $status === 1 ? $definicao['cheio'] : $definicao['meio'];
        }

        return end($pools)['cheio'].' concluída';
    }

    /**
     * @param  array{pool: string, meio: string, cheio: string, usa_introdutorio: bool}  $definicao
     * @return int 0 = nem a metade da piscina, 1 = metade atingida (ou Período Introdutório completo), 2 = 100% da piscina
     */
    protected function statusPoolAntigoPorItem(Jovem $jovem, array $definicao): int
    {
        $itens = ItemAntigo::query()
            ->whereHas('competencia.areaDesenvolvimento', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->where('etapa', $definicao['pool'])
            ->get();

        // Sem itens cadastrados nessa piscina ainda (planilha não importada):
        // considera satisfeita por vacuidade, igual à regra de Bloco sem Obrigatórias (Fase 5).
        if ($itens->isEmpty()) {
            return 2;
        }

        $itemConcluido = fn (ItemAntigo $item) => $this->creditoService->itemAntigoConcluido($jovem, $item);

        if ($itens->every($itemConcluido)) {
            return 2;
        }

        if ($definicao['usa_introdutorio']) {
            $introdutorios = $itens->where('introdutorio', true);

            $meioAtingido = $introdutorios->isEmpty() || $introdutorios->every($itemConcluido);
        } else {
            $meioAtingido = $itens->filter($itemConcluido)->count() >= ($itens->count() / 2);
        }

        return $meioAtingido ? 1 : 0;
    }

    protected function etapaAntigoSenior(Jovem $jovem): string
    {
        $fracao = $this->fracaoItensAntigos($jovem)['fracao'];

        return match (true) {
            $fracao >= 1.0 => 'Azimute concluída',
            $fracao >= 2 / 3 => 'Azimute',
            $fracao >= 1 / 3 => 'Conquista',
            default => 'Escalada',
        };
    }

    protected function etapaAntigoPioneiro(Jovem $jovem): string
    {
        $fracao = $this->fracaoItensAntigos($jovem)['fracao'];

        if ($fracao >= 1.0) {
            return 'Insígnia BP alcançável';
        }

        if ($fracao >= 0.5) {
            $flagsOk = $jovem->requisitoBool('pioneiro_antigo_projeto_em_andamento')
                && $jovem->requisitoBool('pioneiro_antigo_plano_desenvolvimento_pessoal');

            return $flagsOk ? 'Cidadania' : 'Cidadania (pendente: projeto/plano)';
        }

        return 'Comprometimento';
    }

    // ------------------------------------------------------------------
    // etapaNovo
    // ------------------------------------------------------------------

    public function etapaNovo(Jovem $jovem): string
    {
        $cortes = self::CORTES_ETAPA_NOVO[$jovem->ramoAtual->nome] ?? null;

        if ($cortes === null) {
            return 'Indefinida';
        }

        $concluidos = $this->statusService->percentualNovo($jovem)['concluidos'];

        foreach ($cortes as [$maximo, $label]) {
            if ($concluidos <= $maximo) {
                return $label;
            }
        }

        return end($cortes)[1];
    }

    // ------------------------------------------------------------------
    // Elegibilidade ao Reconhecimento
    // ------------------------------------------------------------------

    public function elegivelReconhecimentoAntigo(Jovem $jovem): bool
    {
        if ($this->fracaoItensAntigos($jovem)['fracao'] < 1.0) {
            return false;
        }

        $ramoNome = $jovem->ramoAtual->nome;

        if ($ramoNome === 'Pioneiro') {
            return $jovem->requisitoBool('pioneiro_antigo_projeto_relevante_executado')
                && $jovem->requisitoBool('pioneiro_antigo_revisao_plano_pessoal');
        }

        foreach ($this->chavesComplementaresAntigo($ramoNome) as $chaveDef) {
            if (! $this->chaveSatisfeita($jovem, $chaveDef)) {
                return false;
            }
        }

        return true;
    }

    public function elegivelReconhecimentoNovo(Jovem $jovem): bool
    {
        if ($this->statusService->percentualNovo($jovem)['concluidos'] !== 18) {
            return false;
        }

        foreach ($this->chavesComplementaresNovo($jovem->ramoAtual->nome) as $chaveDef) {
            if (! $this->chaveSatisfeita($jovem, $chaveDef)) {
                return false;
            }
        }

        return true;
    }
}
