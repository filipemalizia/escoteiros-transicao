<?php

namespace App\Services;

use App\Models\EspecialidadeDistintivo;
use App\Models\EspecialidadeDistintivoGrupo;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;

/**
 * Calcula o status de uma Especialidade/Insígnia pra um jovem — extraído de
 * {@see StatusProgressaoService} pra poder ser usado também por
 * {@see EquivalenciaCreditoService} (que credita um ItemNovo como concluído
 * quando a especialidade vinculada via `EquivalenciaEspecialidade` está
 * conquistada) sem criar dependência circular: `StatusProgressaoService` já
 * depende de `EquivalenciaCreditoService` pra crédito de bloco/competência.
 *
 * Registrado como singleton no container pelo mesmo motivo dos outros dois
 * serviços — cache memoizado por (jovem, especialidade), válido só durante a
 * vida da requisição.
 */
class EspecialidadeStatusService
{
    /** @var array<string, array<string, mixed>> */
    private array $cacheStatusEspecialidade = [];

    /** @var array<int, array<int, int>> */
    private array $cacheItensEspecialidadeConcluidos = [];

    public function limparCache(): void
    {
        $this->cacheStatusEspecialidade = [];
        $this->cacheItensEspecialidadeConcluidos = [];
    }

    /**
     * @return array{status: string, nivel_atingido: int|null, itens_concluidos: int, itens_totais: int, grupos: array<int, array{grupo: EspecialidadeDistintivoGrupo, concluidos: int, necessarios: int, necessarios_totais: int, satisfeito: bool}>}
     */
    public function statusEspecialidade(Jovem $jovem, EspecialidadeDistintivo $especialidade): array
    {
        $chaveCache = "{$jovem->id}:{$especialidade->id}";

        return $this->cacheStatusEspecialidade[$chaveCache] ??= $this->calcularStatusEspecialidade($jovem, $especialidade);
    }

    /**
     * @return array{status: string, nivel_atingido: int|null, itens_concluidos: int, itens_totais: int, grupos: array<int, array{grupo: EspecialidadeDistintivoGrupo, concluidos: int, necessarios: int, necessarios_totais: int, satisfeito: bool}>}
     */
    private function calcularStatusEspecialidade(Jovem $jovem, EspecialidadeDistintivo $especialidade): array
    {
        $gruposStatus = $especialidade->grupos->map(fn (EspecialidadeDistintivoGrupo $grupo) => [
            'grupo' => $grupo,
            ...$this->statusGrupoEspecialidade($jovem, $grupo),
        ]);

        // Insígnia nunca tem níveis, mesmo quando usa o layout de lista única
        // (itens_niveis) — pra ela, conclusão é sempre "todos os grupos
        // satisfeitos", igual atividades_temas.
        if ($especialidade->estrutura === 'itens_niveis' && $especialidade->tipo !== 'Insígnia') {
            $grupoItens = $gruposStatus->firstWhere('grupo.chave', 'itens');
            $itensConcluidos = $grupoItens['concluidos'] ?? 0;
            $itensTotais = $grupoItens['necessarios_totais'] ?? 0;

            $nivelAtingido = match (true) {
                $especialidade->minimo_nivel_2 !== null && $itensConcluidos >= $especialidade->minimo_nivel_2 => 2,
                $especialidade->minimo_nivel_1 !== null && $itensConcluidos >= $especialidade->minimo_nivel_1 => 1,
                default => 0,
            };

            $status = match (true) {
                $nivelAtingido >= 1 => 'Concluído',
                $itensConcluidos > 0 => 'Parcial',
                default => 'Pendente',
            };

            return [
                'status' => $status,
                'nivel_atingido' => $nivelAtingido,
                'itens_concluidos' => $itensConcluidos,
                'itens_totais' => $itensTotais,
                'grupos' => $gruposStatus->all(),
            ];
        }

        $todosSatisfeitos = $gruposStatus->isNotEmpty() && $gruposStatus->every(fn (array $g) => $g['satisfeito']);
        $algumConcluido = $gruposStatus->contains(fn (array $g) => $g['concluidos'] > 0);

        $status = match (true) {
            $todosSatisfeitos => 'Concluído',
            $algumConcluido => 'Parcial',
            default => 'Pendente',
        };

        return [
            'status' => $status,
            'nivel_atingido' => null,
            'itens_concluidos' => $gruposStatus->sum('concluidos'),
            'itens_totais' => $gruposStatus->sum('necessarios_totais'),
            'grupos' => $gruposStatus->all(),
        ];
    }

    /**
     * @return array{concluidos: int, necessarios: int, necessarios_totais: int, satisfeito: bool}
     */
    public function statusGrupoEspecialidade(Jovem $jovem, EspecialidadeDistintivoGrupo $grupo): array
    {
        $itens = $grupo->itens;
        $necessariosTotais = $itens->count();
        // null = todos os itens do grupo são obrigatórios (igual quantidade_minima_variaveis do Bloco).
        $necessarios = $grupo->quantidade_minima ?? $necessariosTotais;

        $concluidos = $itens
            ->filter(fn (EspecialidadeDistintivoItem $item) => $this->itemEspecialidadeConcluido($jovem, $item))
            ->count();

        return [
            'concluidos' => $concluidos,
            'necessarios' => $necessarios,
            'necessarios_totais' => $necessariosTotais,
            'satisfeito' => $necessarios === 0 || $concluidos >= $necessarios,
        ];
    }

    private function itemEspecialidadeConcluido(Jovem $jovem, EspecialidadeDistintivoItem $item): bool
    {
        return isset($this->itensEspecialidadeConcluidos($jovem)[$item->id]);
    }

    /**
     * @return array<int, int>
     */
    private function itensEspecialidadeConcluidos(Jovem $jovem): array
    {
        return $this->cacheItensEspecialidadeConcluidos[$jovem->id] ??= ProgressoEspecialidade::query()
            ->where('jovem_id', $jovem->id)
            ->where('concluido', true)
            ->pluck('especialidade_distintivo_item_id')
            ->flip()
            ->all();
    }
}
