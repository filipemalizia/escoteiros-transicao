<?php

namespace App\Support;

/**
 * Realça (com <mark>) as ocorrências do termo buscado dentro de um texto,
 * pra dar destaque visual nos resultados de busca. Escapa o texto original
 * primeiro — o retorno é HTML já seguro pra imprimir com `{!! !!}`.
 *
 * Encontra a posição dos trechos casando contra a versão normalizada (sem
 * acento, minúscula — ver {@see Busca::normalizar()}) do texto, mas destaca
 * o trecho correspondente no texto ORIGINAL (com acento) — funciona porque
 * a normalização troca 1 caractere por 1 caractere (confirmado pros acentos
 * do português), então as posições batem entre as duas versões.
 */
class Destaque
{
    public static function html(?string $texto, ?string $busca): string
    {
        $texto = (string) $texto;
        $palavras = Busca::palavras((string) $busca);

        if ($palavras === [] || $texto === '') {
            return e($texto);
        }

        $normalizado = Busca::normalizar($texto);

        $trechos = [];

        foreach ($palavras as $palavra) {
            $alvo = Busca::normalizar($palavra);

            if ($alvo === '') {
                continue;
            }

            $tamanhoAlvo = mb_strlen($alvo);
            $offset = 0;

            while (($posicao = mb_strpos($normalizado, $alvo, $offset)) !== false) {
                $trechos[] = [$posicao, $posicao + $tamanhoAlvo];
                $offset = $posicao + 1;
            }
        }

        if ($trechos === []) {
            return e($texto);
        }

        usort($trechos, fn (array $a, array $b) => $a[0] <=> $b[0]);

        // Mescla trechos sobrepostos/adjacentes (ex.: duas palavras da busca
        // que casam em posições que se cruzam), pra nunca aninhar <mark>.
        $mesclados = [];
        foreach ($trechos as [$inicio, $fim]) {
            $ultimo = array_key_last($mesclados);

            if ($ultimo !== null && $inicio <= $mesclados[$ultimo][1]) {
                $mesclados[$ultimo][1] = max($mesclados[$ultimo][1], $fim);
            } else {
                $mesclados[] = [$inicio, $fim];
            }
        }

        $html = '';
        $cursor = 0;

        foreach ($mesclados as [$inicio, $fim]) {
            $html .= e(mb_substr($texto, $cursor, $inicio - $cursor));
            $html .= '<mark class="rounded bg-amber-200 px-0.5 text-inherit dark:bg-amber-500/50">'
                .e(mb_substr($texto, $inicio, $fim - $inicio))
                .'</mark>';
            $cursor = $fim;
        }

        $html .= e(mb_substr($texto, $cursor));

        return $html;
    }
}
