<?php

namespace App\Services;

use App\Models\Equivalencia;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoNovo;

/**
 * Calcula se um item (antigo ou novo) conta como concluído para um jovem,
 * considerando tanto a marcação direta (Fase 4) quanto o crédito cruzado
 * vindo de equivalências com o outro sistema (Fase 6).
 */
class EquivalenciaCreditoService
{
    /**
     * Limite de profundidade da recursão, como proteção contra equivalências
     * mal cadastradas formando ciclos (ex.: A equivale a B, e B equivale a A).
     */
    private const PROFUNDIDADE_MAXIMA = 20;

    /**
     * @param  array<int, string>  $visitados  chaves "antigo:{id}"/"novo:{id}" já percorridas nesta cadeia
     */
    public function itemAntigoConcluido(Jovem $jovem, ItemAntigo $item, array $visitados = []): bool
    {
        $chave = "antigo:{$item->id}";

        if (in_array($chave, $visitados, true) || count($visitados) >= self::PROFUNDIDADE_MAXIMA) {
            return false;
        }

        $marcadoDireto = ProgressoAntigo::query()
            ->where('jovem_id', $jovem->id)
            ->where('item_antigo_id', $item->id)
            ->where('concluido', true)
            ->exists();

        if ($marcadoDireto) {
            return true;
        }

        $equivalencias = Equivalencia::query()
            ->where('item_antigo_id', $item->id)
            ->get();

        if ($equivalencias->isEmpty()) {
            return false;
        }

        $visitados[] = $chave;

        $itensNovos = ItemNovo::query()
            ->whereIn('id', $equivalencias->pluck('item_novo_id')->unique())
            ->get();

        foreach ($itensNovos as $itemNovo) {
            if (! $this->itemNovoConcluido($jovem, $itemNovo, $visitados)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $visitados  chaves "antigo:{id}"/"novo:{id}" já percorridas nesta cadeia
     */
    public function itemNovoConcluido(Jovem $jovem, ItemNovo $item, array $visitados = []): bool
    {
        $chave = "novo:{$item->id}";

        if (in_array($chave, $visitados, true) || count($visitados) >= self::PROFUNDIDADE_MAXIMA) {
            return false;
        }

        $marcadoDireto = ProgressoNovo::query()
            ->where('jovem_id', $jovem->id)
            ->where('item_novo_id', $item->id)
            ->where('concluido', true)
            ->exists();

        if ($marcadoDireto) {
            return true;
        }

        $equivalencias = Equivalencia::query()
            ->where('item_novo_id', $item->id)
            ->get();

        if ($equivalencias->isEmpty()) {
            return false;
        }

        $visitados[] = $chave;

        $itensAntigos = ItemAntigo::query()
            ->whereIn('id', $equivalencias->pluck('item_antigo_id')->unique())
            ->get();

        foreach ($itensAntigos as $itemAntigo) {
            if (! $this->itemAntigoConcluido($jovem, $itemAntigo, $visitados)) {
                return false;
            }
        }

        return true;
    }
}
