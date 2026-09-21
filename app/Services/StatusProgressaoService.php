<?php

namespace App\Services;

use App\Concerns\ExibeProgressoDoJovem;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\EspecialidadeDistintivoGrupo;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Registrado como singleton no container (`AppServiceProvider`), pelo mesmo
 * motivo do `EquivalenciaCreditoService`: a tela de progresso chama
 * `statusBloco()`/`statusCompetencia()` pra CADA bloco/competência a partir
 * de vários métodos diferentes na mesma renderização (resumo, percentual,
 * pendências, etapa, elegibilidade ao Reconhecimento — cada um percorre
 * todos os blocos de novo). Sem cache aqui, cada bloco/competência era
 * recalculado de 3 a 5 vezes por página.
 */
class StatusProgressaoService
{
    /** @var array<string, array<string, mixed>> */
    private array $cacheStatusCompetencia = [];

    /** @var array<string, array<string, mixed>> */
    private array $cacheStatusBloco = [];

    /** @var array<int, array<int, Carbon>> */
    private array $cacheDatasItensEspecialidadeConcluidos = [];

    /** @var array<int, array<int, Carbon>> */
    private array $cacheDatasItensNovosConcluidos = [];

    /** @var array<int, array<int, Carbon>> */
    private array $cacheDatasItensPersonalizadosConcluidos = [];

    public function __construct(
        private readonly EquivalenciaCreditoService $creditoService = new EquivalenciaCreditoService,
        private readonly EspecialidadeStatusService $especialidadeStatusService = new EspecialidadeStatusService,
    ) {}

    /**
     * Esquece todo o cache memoizado (o próprio e o dos serviços injetados)
     * — chamar sempre que `concluido` for alterado em
     * `progresso_antigo`/`progresso_novo`/`progresso_especialidade`.
     */
    public function limparCache(): void
    {
        $this->cacheStatusCompetencia = [];
        $this->cacheStatusBloco = [];
        $this->cacheDatasItensEspecialidadeConcluidos = [];
        $this->cacheDatasItensNovosConcluidos = [];
        $this->cacheDatasItensPersonalizadosConcluidos = [];
        $this->creditoService->limparCache();
        $this->especialidadeStatusService->limparCache();
    }

    /**
     * @return array{status: string, itens_necessarios: int, itens_concluidos: int}
     */
    public function statusCompetencia(Jovem $jovem, CompetenciaAntiga $competencia): array
    {
        $chaveCache = "{$jovem->id}:{$competencia->id}";

        return $this->cacheStatusCompetencia[$chaveCache] ??= $this->calcularStatusCompetencia($jovem, $competencia);
    }

    /**
     * @return array{status: string, itens_necessarios: int, itens_concluidos: int}
     */
    private function calcularStatusCompetencia(Jovem $jovem, CompetenciaAntiga $competencia): array
    {
        $itens = $competencia->itens;
        $itensNecessarios = $itens->count();

        $itensConcluidos = $itens->filter(
            fn (ItemAntigo $item) => $this->creditoService->itemAntigoConcluido($jovem, $item)
        )->count();

        $status = match (true) {
            $itensNecessarios === 0 => 'Concluído',
            $itensConcluidos === $itensNecessarios => 'Concluído',
            $itensConcluidos > 0 => 'Parcial',
            default => 'Pendente',
        };

        return [
            'status' => $status,
            'itens_necessarios' => $itensNecessarios,
            'itens_concluidos' => $itensConcluidos,
        ];
    }

    /**
     * @return array{status: string, obrigatorias_necessarias: int, obrigatorias_concluidas: int, variaveis_necessarias: int, variaveis_concluidas: int, variaveis_concluidas_via_bloco: int, variaveis_concluidas_via_personalizado: int, substitutiva_concluida: bool}
     */
    public function statusBloco(Jovem $jovem, BlocoNovo $bloco): array
    {
        $chaveCache = "{$jovem->id}:{$bloco->id}";

        return $this->cacheStatusBloco[$chaveCache] ??= $this->calcularStatusBloco($jovem, $bloco);
    }

    /**
     * Ordem de exibição por tipo de ação dentro de um bloco — Substitutiva
     * fica por último de propósito (é um "atalho" alternativo às Variáveis,
     * não a ação principal do bloco); itens Personalizados (que não são
     * `ItemNovo`, ficam numa seção à parte na view) entram entre Variável e
     * Substitutiva nessa mesma ordem.
     */
    private const ORDEM_TIPO_ACAO = ['Obrigatória' => 1, 'Variável' => 2, 'Substitutiva' => 4];

    /**
     * Itens do bloco que fazem sentido pra modalidade deste jovem — 'Básica'
     * é visível pra todo mundo, 'Ar'/'Mar' só pra quem é daquela modalidade
     * (herdada da Equipe, ver `Jovem::modalidade()`). Usado tanto no cálculo
     * de status/pendências quanto nas telas (portal e painel do chefe), pra
     * um jovem fora da modalidade nunca ver nem ter contado contra ele um
     * item que não se aplica. Vem ordenado por tipo de ação e, dentro de
     * cada tipo, pelo código.
     *
     * @return Collection<int, ItemNovo>
     */
    public function itensVisiveisDoBloco(Jovem $jovem, BlocoNovo $bloco): Collection
    {
        return $bloco->itens
            ->filter(fn (ItemNovo $item) => in_array($item->modalidade, ['Básica', $jovem->modalidade()], true))
            ->sortBy([
                fn (ItemNovo $a, ItemNovo $b) => (self::ORDEM_TIPO_ACAO[$a->tipo_acao] ?? 99) <=> (self::ORDEM_TIPO_ACAO[$b->tipo_acao] ?? 99),
                fn (ItemNovo $a, ItemNovo $b) => $a->codigo <=> $b->codigo,
            ])
            ->values();
    }

    /**
     * @return array{status: string, obrigatorias_necessarias: int, obrigatorias_concluidas: int, variaveis_necessarias: int, variaveis_concluidas: int, variaveis_concluidas_via_bloco: int, variaveis_concluidas_via_personalizado: int, substitutiva_concluida: bool}
     */
    private function calcularStatusBloco(Jovem $jovem, BlocoNovo $bloco): array
    {
        $itens = $this->itensVisiveisDoBloco($jovem, $bloco);

        $obrigatorias = $itens->where('tipo_acao', 'Obrigatória');
        $variaveis = $itens->where('tipo_acao', 'Variável');
        $substitutivas = $itens->where('tipo_acao', 'Substitutiva');

        $itemConcluido = fn (ItemNovo $item) => $this->creditoService->itemNovoConcluido($jovem, $item);

        $obrigatoriasNecessarias = $obrigatorias->count();
        $obrigatoriasConcluidas = $obrigatorias->filter($itemConcluido)->count();

        $variaveisNecessarias = $bloco->quantidade_minima_variaveis ?? 0;
        $variaveisConcluidas = $variaveis->filter($itemConcluido)->count();

        /**
         * Crédito de bloco: itens do programa antigo que, sozinhos, contam
         * como uma Ação Variável do bloco (sem corresponder a nenhum item
         * novo específico) — ex.: "atividades do programa anterior que
         * podem complementar as atividades variáveis" no documento oficial.
         */
        $variaveisConcluidasViaBloco = $bloco->equivalenciasBloco
            ->pluck('itemAntigo')
            ->filter()
            ->filter(fn (ItemAntigo $item) => $this->creditoService->itemAntigoConcluido($jovem, $item))
            ->count();

        $variaveisConcluidas += $variaveisConcluidasViaBloco;

        /**
         * Itens "Variável" avulsos criados por um adulto pra esse jovem
         * específico (não fazem parte do catálogo oficial — ver
         * ItemPersonalizado) contam igual a um item Variável comum.
         */
        $itensPersonalizadosDoJovem = $this->itensPersonalizadosDoBloco($jovem, $bloco);

        $variaveisConcluidasPersonalizadas = $itensPersonalizadosDoJovem
            ->filter(fn (ItemPersonalizado $item) => $this->itemPersonalizadoConcluido($jovem, $item))
            ->count();

        $variaveisConcluidas += $variaveisConcluidasPersonalizadas;

        $substitutivaConcluida = $substitutivas->contains($itemConcluido);

        $obrigatoriasSatisfeitas = $obrigatoriasNecessarias === 0 || $obrigatoriasConcluidas === $obrigatoriasNecessarias;
        $variaveisSatisfeitas = $variaveisNecessarias === 0 || $variaveisConcluidas >= $variaveisNecessarias;

        /**
         * Um bloco sem nenhum item Obrigatória/Variável/Substitutiva visível
         * pra este jovem (ex.: cadastro incompleto, ou todos os itens são de
         * outra modalidade) nunca conta como "Concluído" só porque as duas
         * condições acima ficam vacuamente satisfeitas (0 de 0) — sem isso,
         * um bloco vazio aparecia 100% concluído pra um jovem recém-criado,
         * sem ele ter feito nada.
         */
        $temAlgumItemAcionavel = $obrigatoriasNecessarias > 0 || $variaveis->isNotEmpty() || $substitutivas->isNotEmpty();

        $concluido = $temAlgumItemAcionavel && $obrigatoriasSatisfeitas && ($variaveisSatisfeitas || $substitutivaConcluida);
        $algumConcluido = $obrigatoriasConcluidas > 0 || $variaveisConcluidas > 0 || $substitutivaConcluida;

        $status = match (true) {
            $concluido => 'Concluído',
            $algumConcluido => 'Parcial',
            default => 'Pendente',
        };

        return [
            'status' => $status,
            'obrigatorias_necessarias' => $obrigatoriasNecessarias,
            'obrigatorias_concluidas' => $obrigatoriasConcluidas,
            'variaveis_necessarias' => $variaveisNecessarias,
            'variaveis_concluidas' => $variaveisConcluidas,
            'variaveis_concluidas_via_bloco' => $variaveisConcluidasViaBloco,
            'variaveis_concluidas_via_personalizado' => $variaveisConcluidasPersonalizadas,
            'substitutiva_concluida' => $substitutivaConcluida,
        ];
    }

    /**
     * Status de uma Especialidade/Insígnia pro jovem. `nivel_atingido` só
     * faz sentido pra estrutura `itens_niveis` (Lobinho/Escoteiro) — os
     * itens não são marcados por nível, é uma contagem cumulativa sobre a
     * mesma lista ("concluir quatro pra nível 1, oito pra nível 2"). Pra
     * `atividades_temas` (Sênior/Pioneiro) não tem nível, é tudo-ou-nada:
     * conquistada quando todos os grupos (conhecer/fazer/compartilhar)
     * estiverem satisfeitos.
     *
     * @return array{status: string, nivel_atingido: int|null, itens_concluidos: int, itens_totais: int, grupos: array<int, array{grupo: EspecialidadeDistintivoGrupo, concluidos: int, necessarios: int, necessarios_totais: int, satisfeito: bool}>}
     */
    public function statusEspecialidade(Jovem $jovem, EspecialidadeDistintivo $especialidade): array
    {
        return $this->especialidadeStatusService->statusEspecialidade($jovem, $especialidade);
    }

    /**
     * @return array{concluidos: int, necessarios: int, necessarios_totais: int, satisfeito: bool}
     */
    public function statusGrupoEspecialidade(Jovem $jovem, EspecialidadeDistintivoGrupo $grupo): array
    {
        return $this->especialidadeStatusService->statusGrupoEspecialidade($jovem, $grupo);
    }

    /**
     * @return array{total: int, concluidas: int, percentual: float}
     */
    public function percentualAntigo(Jovem $jovem): array
    {
        $competencias = CompetenciaAntiga::query()
            ->whereHas('areaDesenvolvimento', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->with('itens')
            ->get();

        $total = $competencias->count();
        $concluidas = $competencias->filter(
            fn (CompetenciaAntiga $competencia) => $this->statusCompetencia($jovem, $competencia)['status'] === 'Concluído'
        )->count();

        return [
            'total' => $total,
            'concluidas' => $concluidas,
            'percentual' => $total > 0 ? round(($concluidas / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function percentualNovo(Jovem $jovem): array
    {
        $blocos = BlocoNovo::query()
            ->whereHas('eixo', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->with('itens')
            ->get();

        $total = $blocos->count();
        $concluidos = $blocos->filter(
            fn (BlocoNovo $bloco) => $this->statusBloco($jovem, $bloco)['status'] === 'Concluído'
        )->count();

        return [
            'total' => $total,
            'concluidos' => $concluidos,
            'percentual' => $total > 0 ? round(($concluidos / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Percentual "por bloco" fracionário — só usado pro portal do jovem, uma
     * métrica mais motivadora que `percentualNovo()` (que só conta bloco
     * 100%/0%, sem meio-termo). NÃO substitui `percentualNovo()`, que
     * continua sendo a régua oficial usada pelo painel do chefe e pelo PDF
     * de pendências.
     *
     * @return array{percentual: float}
     */
    public function percentualGamificadoNovo(Jovem $jovem): array
    {
        $blocos = BlocoNovo::query()
            ->whereHas('eixo', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->with('itens')
            ->get();

        if ($blocos->isEmpty()) {
            return ['percentual' => 0.0];
        }

        $somaPercentuais = $blocos->sum(fn (BlocoNovo $bloco) => $this->percentualGamificadoBloco($jovem, $bloco));

        return [
            'percentual' => round(($somaPercentuais / $blocos->count()) * 100, 1),
        ];
    }

    /**
     * Percentual gamificado (0.0-1.0), só dos Blocos de UM Eixo — usado pro
     * preenchimento "de onda" do card do Eixo na tela Início. Opera sobre
     * `$eixo->blocos` já carregado (`getEixosNovos()` já faz eager load de
     * `blocos.itens`), sem nenhuma query nova.
     */
    public function percentualGamificadoEixo(Jovem $jovem, EixoNovo $eixo): float
    {
        $blocos = $eixo->blocos;

        return $blocos->isEmpty() ? 0.0 : $blocos->avg(
            fn (BlocoNovo $bloco) => $this->percentualGamificadoBloco($jovem, $bloco)
        );
    }

    /**
     * Percentual gamificado (0.0-1.0) de UM Bloco só — usado na barra de
     * progresso por bloco na tela Início, além de já ser reaproveitado
     * internamente por `percentualGamificadoNovo()`/`percentualGamificadoEixo()`.
     */
    public function percentualGamificadoBloco(Jovem $jovem, BlocoNovo $bloco): float
    {
        $status = $this->statusBloco($jovem, $bloco);

        $percentualObrigatorias = $status['obrigatorias_necessarias'] > 0
            ? min($status['obrigatorias_concluidas'] / $status['obrigatorias_necessarias'], 1)
            : 1.0;

        if ($status['variaveis_necessarias'] === 0) {
            return $percentualObrigatorias;
        }

        $percentualVariaveis = min($status['variaveis_concluidas'] / $status['variaveis_necessarias'], 1);

        return ($percentualObrigatorias + $percentualVariaveis) / 2;
    }

    /**
     * Data em que o Bloco foi concluído pra esse jovem — não é uma coluna
     * nova, é derivada: a maior `data_conclusao` entre todas as Obrigatórias
     * e, das Variáveis, só as N mais antigas necessárias pra bater o mínimo
     * exigido (ou a Substitutiva, se foi por ela que o bloco fechou). Um
     * item creditado só por equivalência com o programa antigo (sem
     * `data_conclusao` própria em `progresso_novo`/`progresso_personalizado`)
     * não contribui com uma data aqui — só usada pro portal do jovem, é
     * puramente informativa, então esse detalhe não afeta a regra oficial.
     */
    public function dataConclusaoBloco(Jovem $jovem, BlocoNovo $bloco): ?Carbon
    {
        $status = $this->statusBloco($jovem, $bloco);

        if ($status['status'] !== 'Concluído') {
            return null;
        }

        $datasPorItem = $this->datasItensNovosConcluidos($jovem);
        $itensVisiveis = $this->itensVisiveisDoBloco($jovem, $bloco);

        // `->toBase()` é necessário aqui: `Eloquent\Collection::map()` só
        // vira uma `Support\Collection` sozinho quando o resultado tem pelo
        // menos um item (checa via `contains()`, que numa coleção vazia
        // sempre dá `false`) — um bloco sem nenhuma Obrigatória visível (ou
        // sem nenhuma concluída) chega aqui como uma `Eloquent\Collection`
        // vazia de Carbon, e o `merge()` dela tenta chamar `getKey()` (só
        // existe em Model) em cada item concluído. Forçar a base evita cair
        // nesse `merge()` sobrescrito.
        $datasObrigatorias = $itensVisiveis->where('tipo_acao', 'Obrigatória')
            ->map(fn (ItemNovo $item) => $datasPorItem[$item->id] ?? null)
            ->filter()
            ->toBase();

        $completouViaSubstitutiva = $status['substitutiva_concluida']
            && $status['variaveis_concluidas'] < $status['variaveis_necessarias'];

        if ($completouViaSubstitutiva) {
            $datasSubstitutiva = $itensVisiveis->where('tipo_acao', 'Substitutiva')
                ->map(fn (ItemNovo $item) => $datasPorItem[$item->id] ?? null)
                ->filter()
                ->toBase();

            return $datasObrigatorias->merge($datasSubstitutiva)->max();
        }

        $datasPersonalizadosPorItem = $this->datasItensPersonalizadosConcluidos($jovem);

        $datasVariaveis = $itensVisiveis->where('tipo_acao', 'Variável')
            ->map(fn (ItemNovo $item) => $datasPorItem[$item->id] ?? null)
            ->filter()
            ->toBase()
            ->merge(
                $this->itensPersonalizadosDoBloco($jovem, $bloco)
                    ->map(fn (ItemPersonalizado $item) => $datasPersonalizadosPorItem[$item->id] ?? null)
                    ->filter()
                    ->toBase()
            )
            ->sort()
            ->values()
            ->take($status['variaveis_necessarias']);

        return $datasObrigatorias->merge($datasVariaveis)->max();
    }

    /**
     * @return array<int, Carbon>
     */
    private function datasItensNovosConcluidos(Jovem $jovem): array
    {
        return $this->cacheDatasItensNovosConcluidos[$jovem->id] ??= ProgressoNovo::query()
            ->where('jovem_id', $jovem->id)
            ->where('concluido', true)
            ->pluck('data_conclusao', 'item_novo_id')
            ->all();
    }

    /**
     * @return array<int, Carbon>
     */
    private function datasItensPersonalizadosConcluidos(Jovem $jovem): array
    {
        return $this->cacheDatasItensPersonalizadosConcluidos[$jovem->id] ??= ProgressoPersonalizado::query()
            ->where('jovem_id', $jovem->id)
            ->where('concluido', true)
            ->pluck('data_conclusao', 'item_personalizado_id')
            ->all();
    }

    /**
     * Data em que o jovem atingiu um nível (1 ou 2) de uma Especialidade de
     * estrutura `itens_niveis` (Lobinho/Escoteiro) — não é coluna nova: os
     * itens do grupo "itens" são concluídos cumulativamente (sem nível
     * próprio marcado), então a data do nível N é a `data_conclusao` do
     * item de ordem `minimo_nivel_N`, contando em ordem cronológica de
     * conclusão. Retorna null se o nível ainda não foi atingido, ou se a
     * especialidade não usa essa estrutura.
     */
    public function dataNivelEspecialidade(Jovem $jovem, EspecialidadeDistintivo $especialidade, int $nivel): ?Carbon
    {
        $minimo = $nivel === 2 ? $especialidade->minimo_nivel_2 : $especialidade->minimo_nivel_1;

        if (blank($minimo)) {
            return null;
        }

        $grupoItens = $especialidade->grupos->firstWhere('chave', 'itens');

        if (! $grupoItens) {
            return null;
        }

        $datasPorItem = $this->datasItensEspecialidadeConcluidos($jovem);

        $datas = $grupoItens->itens
            ->map(fn (EspecialidadeDistintivoItem $item) => $datasPorItem[$item->id] ?? null)
            ->filter()
            ->sort()
            ->values();

        return $datas->get($minimo - 1);
    }

    /**
     * Data de conclusão de uma Especialidade/Insígnia de estrutura
     * `atividades_temas` (tudo-ou-nada entre os grupos) — a maior
     * `data_conclusao` entre todos os itens que, juntos, satisfazem todos
     * os grupos. Pra estrutura `itens_niveis` use {@see dataNivelEspecialidade()}.
     */
    public function dataConclusaoEspecialidade(Jovem $jovem, EspecialidadeDistintivo $especialidade): ?Carbon
    {
        if ($this->statusEspecialidade($jovem, $especialidade)['status'] !== 'Concluído') {
            return null;
        }

        $datasPorItem = $this->datasItensEspecialidadeConcluidos($jovem);

        $datas = $especialidade->grupos
            ->flatMap(fn (EspecialidadeDistintivoGrupo $grupo) => $grupo->itens)
            ->map(fn (EspecialidadeDistintivoItem $item) => $datasPorItem[$item->id] ?? null)
            ->filter();

        return $datas->max();
    }

    /**
     * @return array<int, Carbon>
     */
    private function datasItensEspecialidadeConcluidos(Jovem $jovem): array
    {
        return $this->cacheDatasItensEspecialidadeConcluidos[$jovem->id] ??= ProgressoEspecialidade::query()
            ->where('jovem_id', $jovem->id)
            ->where('concluido', true)
            ->pluck('data_conclusao', 'especialidade_distintivo_item_id')
            ->all();
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function resumoAntigo(Jovem $jovem): array
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
            'percentual' => $total > 0 ? round(($concluidos / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{blocos_total: int, blocos_concluidos: int, obrigatorias_total: int, obrigatorias_concluidas: int, variaveis_minimas_total: int, variaveis_atingidas: int}
     */
    public function resumoNovo(Jovem $jovem): array
    {
        $blocos = BlocoNovo::query()
            ->whereHas('eixo', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->with('itens')
            ->get();

        $obrigatoriasTotal = 0;
        $obrigatoriasConcluidas = 0;
        $variaveisMinimasTotal = 0;
        $variaveisAtingidas = 0;
        $blocosConcluidos = 0;

        foreach ($blocos as $bloco) {
            $status = $this->statusBloco($jovem, $bloco);

            $obrigatoriasTotal += $status['obrigatorias_necessarias'];
            $obrigatoriasConcluidas += $status['obrigatorias_concluidas'];
            $variaveisMinimasTotal += $status['variaveis_necessarias'];
            $variaveisAtingidas += min($status['variaveis_concluidas'], $status['variaveis_necessarias']);

            if ($status['status'] === 'Concluído') {
                $blocosConcluidos++;
            }
        }

        return [
            'blocos_total' => 18,
            'blocos_concluidos' => $blocosConcluidos,
            'obrigatorias_total' => $obrigatoriasTotal,
            'obrigatorias_concluidas' => $obrigatoriasConcluidas,
            'variaveis_minimas_total' => $variaveisMinimasTotal,
            'variaveis_atingidas' => $variaveisAtingidas,
        ];
    }

    /**
     * @return array<int, array{bloco: BlocoNovo, status: string, detalhe: string, obrigatorias_pendentes: array<int, ItemNovo>, variaveis_pendentes: array<int, ItemNovo|ItemPersonalizado>}>
     */
    public function pendenciasNovo(Jovem $jovem): array
    {
        $blocos = BlocoNovo::query()
            ->whereHas('eixo', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->with(['itens', 'eixo'])
            ->get();

        $pendencias = [];

        foreach ($blocos as $bloco) {
            $status = $this->statusBloco($jovem, $bloco);

            if ($status['status'] === 'Concluído') {
                continue;
            }

            $obrigatoriasSatisfeitas = $status['obrigatorias_necessarias'] === 0
                || $status['obrigatorias_concluidas'] === $status['obrigatorias_necessarias'];

            $variaveisSatisfeitas = $status['variaveis_necessarias'] === 0
                || $status['variaveis_concluidas'] >= $status['variaveis_necessarias'];

            $detalhes = [];

            $detalhes[] = ($variaveisSatisfeitas || $status['substitutiva_concluida'])
                ? 'Variáveis/Substitutiva OK'
                : sprintf(
                    'Faltam %d Ações Variáveis (tem %d de %d) ou 1 Substitutiva',
                    max(0, $status['variaveis_necessarias'] - $status['variaveis_concluidas']),
                    $status['variaveis_concluidas'],
                    $status['variaveis_necessarias'],
                );

            $detalhes[] = $obrigatoriasSatisfeitas
                ? 'Obrigatórias OK'
                : sprintf(
                    'Faltam %d Ações Obrigatórias (tem %d de %d)',
                    $status['obrigatorias_necessarias'] - $status['obrigatorias_concluidas'],
                    $status['obrigatorias_concluidas'],
                    $status['obrigatorias_necessarias'],
                );

            $itemPendente = fn (ItemNovo $item) => ! $this->creditoService->itemNovoConcluido($jovem, $item);
            $itensVisiveis = $this->itensVisiveisDoBloco($jovem, $bloco);

            $obrigatoriasPendentes = $itensVisiveis
                ->where('tipo_acao', 'Obrigatória')
                ->filter($itemPendente)
                ->values()
                ->all();

            // Só lista as Variáveis pendentes se o bloco ainda não atingiu o
            // mínimo exigido — se já atingiu, itens Variável restantes não
            // fazem mais falta. Itens personalizados pendentes desse jovem
            // entram na mesma lista — contam como Ação Variável igualzinho
            // (ver StatusProgressaoService::statusBloco()).
            $variaveisPendentes = $variaveisSatisfeitas
                ? []
                : [
                    ...$itensVisiveis->where('tipo_acao', 'Variável')->filter($itemPendente)->values()->all(),
                    ...$this->itensPersonalizadosPendentes($jovem, $bloco),
                ];

            $pendencias[] = [
                'bloco' => $bloco,
                'status' => $status['status'],
                'detalhe' => implode(', ', $detalhes),
                'obrigatorias_pendentes' => $obrigatoriasPendentes,
                'variaveis_pendentes' => $variaveisPendentes,
            ];
        }

        return $pendencias;
    }

    /**
     * @return Collection<int, ItemPersonalizado>
     */
    private function itensPersonalizadosDoBloco(Jovem $jovem, BlocoNovo $bloco): Collection
    {
        return ItemPersonalizado::query()
            ->where('bloco_novo_id', $bloco->id)
            ->whereHas('jovens', fn ($query) => $query->where('jovens.id', $jovem->id))
            ->get();
    }

    private function itemPersonalizadoConcluido(Jovem $jovem, ItemPersonalizado $item): bool
    {
        return ProgressoPersonalizado::query()
            ->where('jovem_id', $jovem->id)
            ->where('item_personalizado_id', $item->id)
            ->where('concluido', true)
            ->exists();
    }

    /**
     * @return array<int, ItemPersonalizado>
     */
    private function itensPersonalizadosPendentes(Jovem $jovem, BlocoNovo $bloco): array
    {
        return $this->itensPersonalizadosDoBloco($jovem, $bloco)
            ->reject(fn (ItemPersonalizado $item) => $this->itemPersonalizadoConcluido($jovem, $item))
            ->values()
            ->all();
    }

    /**
     * @return array<int, ItemAntigo>
     */
    public function pendenciasAntigo(Jovem $jovem): array
    {
        $itens = ItemAntigo::query()
            ->whereHas('competencia.areaDesenvolvimento', fn ($query) => $query->where('ramo_id', $jovem->ramo_atual_id))
            ->with('competencia')
            ->get();

        return $itens
            ->reject(fn (ItemAntigo $item) => $this->creditoService->itemAntigoConcluido($jovem, $item))
            ->values()
            ->all();
    }

    /**
     * Contagem "leve" (só `count()`, sem eager load de contexto) de itens
     * que o jovem marcou como feitos e está esperando confirmação de um
     * adulto — usada no badge da aba Revisão, renderizado em toda página do
     * portal (via `components/portal/tabs.blade.php`), então precisa ser
     * barata. A lista completa (com texto/contexto pra exibir) fica em
     * {@see ExibeProgressoDoJovem::getItensAguardandoRevisao()}.
     */
    public function contagemAguardandoRevisao(Jovem $jovem): int
    {
        return ProgressoNovo::query()->where('jovem_id', $jovem->id)->where('solicitado_pelo_jovem', true)->where('concluido', false)->count()
            + ProgressoPersonalizado::query()->where('jovem_id', $jovem->id)->where('solicitado_pelo_jovem', true)->where('concluido', false)->count()
            + ProgressoEspecialidade::query()->where('jovem_id', $jovem->id)->where('solicitado_pelo_jovem', true)->where('concluido', false)->count();
    }
}
