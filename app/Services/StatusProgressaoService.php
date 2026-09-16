<?php

namespace App\Services;

use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoPersonalizado;
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

    public function __construct(
        private readonly EquivalenciaCreditoService $creditoService = new EquivalenciaCreditoService,
    ) {}

    /**
     * Esquece todo o cache memoizado (o próprio e o do
     * {@see EquivalenciaCreditoService} injetado) — chamar sempre que
     * `concluido` for alterado em `progresso_antigo`/`progresso_novo`.
     */
    public function limparCache(): void
    {
        $this->cacheStatusCompetencia = [];
        $this->cacheStatusBloco = [];
        $this->creditoService->limparCache();
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
     * @return array{status: string, obrigatorias_necessarias: int, obrigatorias_concluidas: int, variaveis_necessarias: int, variaveis_concluidas: int, variaveis_concluidas_via_bloco: int, variaveis_concluidas_via_personalizado: int, substitutiva_concluida: bool}
     */
    private function calcularStatusBloco(Jovem $jovem, BlocoNovo $bloco): array
    {
        $itens = $bloco->itens;

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

        $concluido = $obrigatoriasSatisfeitas && ($variaveisSatisfeitas || $substitutivaConcluida);
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

            $obrigatoriasPendentes = $bloco->itens
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
                    ...$bloco->itens->where('tipo_acao', 'Variável')->filter($itemPendente)->values()->all(),
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
}
