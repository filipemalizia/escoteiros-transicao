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
                fn ($valor) => $this->normalizarCabecalho($valor) === $nomeEsperado
            );

            $mapa[$chave] = $indice === false ? null : $indice;
        }

        return $mapa;
    }

    /**
     * Remove o BOM UTF-8 (bytes invisíveis que o Google Planilhas grava no
     * início da primeira célula ao exportar CSV) e normaliza espaços —
     * incluindo o espaço não separável ( ), comum em textos colados do
     * Google Docs/Planilhas — antes de comparar com o nome esperado da
     * coluna.
     */
    protected function normalizarCabecalho(mixed $valor): string
    {
        $valor = (string) $valor;

        $bomUtf8 = "\xEF\xBB\xBF";

        if (str_starts_with($valor, $bomUtf8)) {
            $valor = substr($valor, strlen($bomUtf8));
        }

        $valor = preg_replace('/[\s\x{00A0}]+/u', ' ', $valor);

        return trim($valor);
    }

    /**
     * @param  array<string, int|null>  $cabecalho
     * @param  array<int, string>  $chavesEssenciais
     */
    protected function cabecalhoTemColunasEssenciais(array $cabecalho, array $chavesEssenciais): bool
    {
        foreach ($chavesEssenciais as $chave) {
            if (($cabecalho[$chave] ?? null) === null) {
                return false;
            }
        }

        return true;
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
