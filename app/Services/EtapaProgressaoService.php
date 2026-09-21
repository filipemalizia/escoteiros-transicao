<?php

namespace App\Services;

use App\Models\BlocoNovo;
use App\Models\ItemAntigo;
use App\Models\Jovem;
use App\Models\Ramo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
     * Cortes de etapa do programa novo, por quantidade de Blocos concluídos
     * (sempre 18 no total). Cada par é [quantidade_maxima_inclusive,
     * nome_da_etapa] — a última etapa de cada ramo já bate exatamente nos
     * 18 blocos (não sobra corte "a mais" pro Reconhecimento; ele exige os
     * mesmos 18 blocos, só que com os requisitos complementares também).
     */
    protected const CORTES_ETAPA_NOVO = [
        'Lobinho' => [[4, 'Pata Tenra'], [8, 'Saltador'], [13, 'Rastreador'], [18, 'Caçador']],
        'Escoteiro' => [[4, 'Pistas'], [8, 'Trilha'], [13, 'Rumo'], [18, 'Travessia']],
        'Sênior' => [[6, 'Escalada'], [12, 'Conquista'], [18, 'Azimute']],
        'Pioneiro' => [[6, 'Descoberta'], [12, 'Destino'], [18, 'Horizonte']],
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

    /**
     * Extensões aceitas pra imagem estática de etapa/reconhecimento, na
     * ordem em que são procuradas — o nome do arquivo em si não carrega
     * extensão nos mapas abaixo, só o "slug"; `resolverImagemEstatica()`
     * testa cada uma até achar o arquivo (svg é o que você está usando).
     */
    protected const EXTENSOES_IMAGEM_ESTATICA = ['svg', 'png', 'webp', 'jpg', 'jpeg'];

    /**
     * Slug do arquivo (dentro de `public/images/reconhecimentos/`, sem
     * extensão) do distintivo máximo de cada ramo — mesmo raciocínio das
     * imagens de etapa (arquivo estático, sem upload pelo admin). Uma
     * entrada por ramo só, não por sistema/antigo-novo: é o mesmo
     * distintivo físico nos dois (só o texto do nome varia um pouco entre
     * `NOMES_RECONHECIMENTO['antigo']` e `['novo']` pra Sênior/Pioneiro).
     */
    protected const IMAGENS_RECONHECIMENTO = [
        'Lobinho' => 'cruzeiro-do-sul',
        'Escoteiro' => 'lis-de-ouro',
        'Sênior' => 'escoteiro-da-patria',
        'Pioneiro' => 'insignia-de-bp',
    ];

    /**
     * Slug do arquivo (dentro de `public/images/etapas/`, sem extensão)
     * pra cada etapa do Programa Novo — arquivo estático subido direto no
     * repo/servidor (não tem upload pelo admin, são poucas imagens e mudam
     * raramente). Sem prefixo de ramo no nome do arquivo de propósito: os
     * 14 nomes de etapa são únicos entre si (nenhum se repete em outro
     * ramo), então o nome puro já basta — mantido agrupado por ramo aqui
     * só pra organização do código. Sem entrada aqui, ou sem o arquivo
     * físico (em nenhuma das `EXTENSOES_IMAGEM_ESTATICA`), `imagemEtapa()`
     * simplesmente não mostra imagem — nunca quebra a tela.
     */
    protected const IMAGENS_ETAPA_NOVO = [
        'Lobinho' => [
            'Pata Tenra' => 'pata-tenra',
            'Saltador' => 'saltador',
            'Rastreador' => 'rastreador',
            'Caçador' => 'cacador',
        ],
        'Escoteiro' => [
            'Pistas' => 'pistas',
            'Trilha' => 'trilha',
            'Rumo' => 'rumo',
            'Travessia' => 'travessia',
        ],
        'Sênior' => [
            'Escalada' => 'escalada',
            'Conquista' => 'conquista',
            'Azimute' => 'azimute',
        ],
        'Pioneiro' => [
            'Descoberta' => 'descoberta',
            'Destino' => 'destino',
            'Horizonte' => 'horizonte',
        ],
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
                ['chave' => 'lobinho_novo_avaliacao_pares', 'tipo' => 'booleano', 'label' => 'Avaliação dos pares e autoavaliação'],
            ],
            'Escoteiro' => [
                ['chave' => 'escoteiro_novo_desafio_pessoal_travessia', 'tipo' => 'booleano', 'label' => 'Desafio pessoal da Travessia'],
                ['chave' => 'escoteiro_novo_avaliacao_pares', 'tipo' => 'booleano', 'label' => 'Avaliação dos pares e autoavaliação'],
            ],
            'Sênior' => [
                ['chave' => 'senior_novo_desafio_pessoal', 'tipo' => 'booleano', 'label' => 'Desafio pessoal'],
                ['chave' => 'senior_novo_avaliacao_pares', 'tipo' => 'booleano', 'label' => 'Avaliação dos pares e autoavaliação'],
            ],
            'Pioneiro' => [
                ['chave' => 'pioneiro_novo_desafio_pessoal', 'tipo' => 'booleano', 'label' => 'Desafio pessoal'],
                ['chave' => 'pioneiro_novo_avaliacao_pares', 'tipo' => 'booleano', 'label' => 'Avaliação dos pares e autoavaliação'],
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

    /**
     * Trilha completa de progressão do Programa Novo pra tela Início: um
     * marco por Etapa do ramo (4 pra Lobinho/Escoteiro, 3 pra Sênior/
     * Pioneiro — a última já bate exatamente nos 18 blocos) seguida por um
     * marco final do Reconhecimento — o distintivo máximo do ramo, que só
     * fica "alcançado" quando `elegivelReconhecimentoNovo()` for true (os
     * mesmos 18 blocos da última Etapa, E os requisitos complementares,
     * não só o corte de blocos).
     *
     * `progresso` é o preenchimento (0.0-1.0) de cada distintivo — **não**
     * é relativo ao corte anterior, é `concluidos / corte_desse_marco`
     * (capado em 1.0). Como os cortes são cumulativos (todos contam a
     * partir do mesmo conjunto de 18 blocos, cada Etapa exigindo mais que
     * a anterior), blocos já concluídos hoje contam de verdade a favor de
     * QUALQUER Etapa futura também — ex.: 4 de 18 blocos já é ~22% de
     * caminho andado rumo à Travessia, não 0%, mesmo sem ter fechado as
     * etapas intermediárias ainda. Isso forma uma "escada" onde cada
     * distintivo mais distante mostra uma fração menor (mesmo numerador,
     * denominador maior), sempre com um número real, nunca inventado.
     *
     * Pro Reconhecimento (cujo corte de blocos é sempre igual ao da última
     * Etapa) o preenchimento é a média entre o progresso de blocos
     * (`concluidos/18`) e a fração de requisitos complementares
     * satisfeitos, senão o distintivo apareceria sempre 100% cheio assim
     * que a última Etapa fosse alcançada, mesmo com requisitos pendentes.
     *
     * `data_alcancado` é a data em que aquele corte de blocos foi batido
     * (o N-ésimo bloco concluído, em ordem cronológica — ver
     * `dataCorteEtapaNovo()`), null se ainda não alcançou.
     *
     * @return array<int, array{tipo: string, label: string, imagem_url: ?string, imagem_data_uri: ?string, alcancado: bool, atual: bool, faltam: int, progresso: float, data_alcancado: ?Carbon}>
     */
    public function trilhaEtapaNovo(Jovem $jovem): array
    {
        $cortes = self::CORTES_ETAPA_NOVO[$jovem->ramoAtual->nome] ?? null;

        if ($cortes === null) {
            return [];
        }

        $concluidos = $this->statusService->percentualNovo($jovem)['concluidos'];
        $ramoNome = $jovem->ramoAtual->nome;

        $trilha = [];

        foreach ($cortes as [$maximo, $label]) {
            $alcancado = $concluidos >= $maximo;

            $trilha[] = [
                'tipo' => 'etapa',
                'label' => $label,
                'imagem_url' => $this->imagemEtapa($ramoNome, $label),
                // Só computa a data URI (leitura de arquivo + base64) pros marcos já
                // alcançados - é a única situação em que o botão de compartilhar aparece.
                'imagem_data_uri' => $alcancado ? $this->dataUriImagemEtapa($ramoNome, $label) : null,
                'alcancado' => $alcancado,
                'faltam' => max(0, $maximo - $concluidos),
                'progresso' => min(1.0, $concluidos / $maximo),
                'data_alcancado' => $alcancado ? $this->dataCorteEtapaNovo($jovem, $maximo) : null,
            ];
        }

        $elegivelReconhecimento = $this->elegivelReconhecimentoNovo($jovem);
        $progressoBlocos = min(1.0, $concluidos / 18);
        $progressoRequisitos = $this->fracaoRequisitosComplementaresNovo($jovem);

        $trilha[] = [
            'tipo' => 'reconhecimento',
            'label' => $this->nomeReconhecimento($jovem->ramoAtual, 'novo'),
            'imagem_url' => $this->imagemReconhecimento($jovem->ramoAtual),
            'imagem_data_uri' => $elegivelReconhecimento ? $this->dataUriImagemReconhecimento($jovem->ramoAtual) : null,
            'alcancado' => $elegivelReconhecimento,
            'faltam' => max(0, 18 - $concluidos),
            'progresso' => ($progressoBlocos + $progressoRequisitos) / 2,
            'data_alcancado' => $elegivelReconhecimento ? $this->dataCorteEtapaNovo($jovem, 18) : null,
        ];

        $indiceAtual = collect($trilha)->search(fn (array $marco) => ! $marco['alcancado']);

        foreach ($trilha as $indice => &$marco) {
            $marco['atual'] = $indiceAtual === false
                ? $indice === array_key_last($trilha)
                : $indice === $indiceAtual;
        }

        unset($marco);

        return $trilha;
    }

    /**
     * Data em que o jovem bateu um corte específico de blocos concluídos
     * (ex.: o 4º bloco concluído, em ordem cronológica, pra saber quando
     * "Pata Tenra" foi alcançada) — mesma ideia de
     * {@see StatusProgressaoService::dataNivelEspecialidade()}, só que sobre
     * blocos em vez de itens de especialidade. Null se o jovem ainda não
     * chegou nesse corte, ou se algum dos blocos que fecharam foi creditado
     * só por equivalência (sem `data_conclusao` própria).
     */
    public function dataCorteEtapaNovo(Jovem $jovem, int $corte): ?Carbon
    {
        $blocos = BlocoNovo::query()
            ->whereHas('eixo', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->with('itens')
            ->get();

        $datas = $blocos
            ->map(fn (BlocoNovo $bloco) => $this->statusService->dataConclusaoBloco($jovem, $bloco))
            ->filter()
            ->sort()
            ->values();

        return $datas->get($corte - 1);
    }

    /**
     * Fração (0.0-1.0) dos requisitos complementares do Programa Novo já
     * satisfeitos pro ramo do jovem — usada só pra compor o preenchimento
     * do distintivo de Reconhecimento na trilha (Fase 12), não pra
     * elegibilidade oficial (que continua em `elegivelReconhecimentoNovo()`,
     * exigindo TODOS, não uma fração).
     */
    private function fracaoRequisitosComplementaresNovo(Jovem $jovem): float
    {
        $chaves = $this->chavesComplementaresNovo($jovem->ramoAtual->nome);

        if (empty($chaves)) {
            return 1.0;
        }

        $satisfeitos = collect($chaves)->filter(fn (array $chaveDef) => $this->chaveSatisfeita($jovem, $chaveDef))->count();

        return $satisfeitos / count($chaves);
    }

    /**
     * URL da imagem da etapa do Programa Novo, se o arquivo já foi subido
     * (ver `public/images/etapas/`). `$nomeEtapa` aceita tanto o nome puro
     * ("Saltador") quanto a variante "X concluído(a)" que `etapaNovo()`
     * devolve na última etapa — as duas usam a mesma imagem.
     */
    public function imagemEtapa(string $ramoNome, string $nomeEtapa): ?string
    {
        $nomeBase = preg_replace('/\s+conclu[ií]d[ao]$/u', '', $nomeEtapa);
        $slug = self::IMAGENS_ETAPA_NOVO[$ramoNome][$nomeBase] ?? null;

        return $slug ? $this->resolverImagemEstatica('images/etapas', $slug) : null;
    }

    /**
     * URL da imagem do distintivo máximo (Reconhecimento) do ramo, se o
     * arquivo já foi subido (ver `public/images/reconhecimentos/`).
     */
    public function imagemReconhecimento(Ramo $ramo): ?string
    {
        $slug = self::IMAGENS_RECONHECIMENTO[$ramo->nome] ?? null;

        return $slug ? $this->resolverImagemEstatica('images/reconhecimentos', $slug) : null;
    }

    /**
     * Mesma imagem de {@see imagemEtapa()}, mas como data URI base64 em vez
     * de URL — usado só pelo cartão de conquista compartilhável (ver
     * {@see ImagemDataUriService}).
     */
    public function dataUriImagemEtapa(string $ramoNome, string $nomeEtapa): ?string
    {
        $nomeBase = preg_replace('/\s+conclu[ií]d[ao]$/u', '', $nomeEtapa);
        $slug = self::IMAGENS_ETAPA_NOVO[$ramoNome][$nomeBase] ?? null;

        return $slug ? app(ImagemDataUriService::class)->paraCaminho($this->caminhoImagemEstatica('images/etapas', $slug)) : null;
    }

    /**
     * Mesma imagem de {@see imagemReconhecimento()}, mas como data URI
     * base64 em vez de URL — usado só pelo cartão de conquista
     * compartilhável (ver {@see ImagemDataUriService}).
     */
    public function dataUriImagemReconhecimento(Ramo $ramo): ?string
    {
        $slug = self::IMAGENS_RECONHECIMENTO[$ramo->nome] ?? null;

        return $slug ? app(ImagemDataUriService::class)->paraCaminho($this->caminhoImagemEstatica('images/reconhecimentos', $slug)) : null;
    }

    /**
     * Procura `{$pasta}/{$slug}.{ext}` em `public/`, testando cada extensão
     * de `EXTENSOES_IMAGEM_ESTATICA` na ordem, e devolve a URL pública da
     * primeira que existir — ou `null` se nenhuma existir ainda.
     */
    private function resolverImagemEstatica(string $pasta, string $slug): ?string
    {
        $caminho = $this->caminhoImagemEstatica($pasta, $slug);

        return $caminho ? asset(Str::after($caminho, public_path().'/')) : null;
    }

    /**
     * Caminho absoluto de `{$pasta}/{$slug}.{ext}` dentro de `public/`,
     * testando cada extensão de `EXTENSOES_IMAGEM_ESTATICA` na ordem — ou
     * `null` se nenhuma existir ainda.
     */
    private function caminhoImagemEstatica(string $pasta, string $slug): ?string
    {
        foreach (self::EXTENSOES_IMAGEM_ESTATICA as $extensao) {
            $caminhoAbsoluto = public_path("{$pasta}/{$slug}.{$extensao}");

            if (file_exists($caminhoAbsoluto)) {
                return $caminhoAbsoluto;
            }
        }

        return null;
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
