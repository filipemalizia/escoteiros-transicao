<?php

namespace App\Services;

use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;

class StatusProgressaoService
{
    public function __construct(
        private readonly EquivalenciaCreditoService $creditoService = new EquivalenciaCreditoService,
    ) {}

    /**
     * @return array{status: string, itens_necessarios: int, itens_concluidos: int}
     */
    public function statusCompetencia(Jovem $jovem, CompetenciaAntiga $competencia): array
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
     * @return array{status: string, obrigatorias_necessarias: int, obrigatorias_concluidas: int, variaveis_necessarias: int, variaveis_concluidas: int, substitutiva_concluida: bool}
     */
    public function statusBloco(Jovem $jovem, BlocoNovo $bloco): array
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
     * @return array<int, array{bloco: BlocoNovo, status: string, detalhe: string}>
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

            $pendencias[] = [
                'bloco' => $bloco,
                'status' => $status['status'],
                'detalhe' => implode(', ', $detalhes),
            ];
        }

        return $pendencias;
    }
}
