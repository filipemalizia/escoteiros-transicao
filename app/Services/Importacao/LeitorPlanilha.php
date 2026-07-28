<?php

namespace App\Services\Importacao;

use Illuminate\Support\Collection;

/**
 * Helpers compartilhados de leitura por nome de cabeçalho (não por posição).
 */
trait LeitorPlanilha
{
    /**
     * @param  array<string, string>  $colunasEsperadas  chave interna => nome exato do cabeçalho
     * @return array<string, int|null> chave interna => índice da coluna (ou null se não encontrada)
     */
    protected function mapearCabecalho(Collection $linhaCabecalho, array $colunasEsperadas): array
    {
        $mapa = [];

        foreach ($colunasEsperadas as $chave => $nomeEsperado) {
            $indice = $linhaCabecalho->search(
                fn ($valor) => trim((string) $valor) === $nomeEsperado
            );

            $mapa[$chave] = $indice === false ? null : $indice;
        }

        return $mapa;
    }

    /**
     * @param  array<string, int|null>  $cabecalho
     */
    protected function valor(Collection $linha, array $cabecalho, string $chave): ?string
    {
        $indice = $cabecalho[$chave] ?? null;

        if ($indice === null) {
            return null;
        }

        $valor = $linha->get($indice);
        $valor = is_string($valor) ? trim($valor) : $valor;

        return ($valor === '' || $valor === null) ? null : (string) $valor;
    }
}
