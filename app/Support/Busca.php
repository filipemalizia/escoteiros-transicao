<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Busca "tipo Google": quebra o termo digitado em palavras e trata cada uma
 * independente — não precisa ser uma frase exata nem estar no mesmo campo,
 * só precisa aparecer em algum lugar do conjunto de textos considerados.
 * Também ignora acentos (e maiúsculas/minúsculas): "ferias" encontra
 * "Férias" e vice-versa.
 */
class Busca
{
    /**
     * Minúsculo + sem acento — "Férias"/"ferias"/"FERIAS" viram todos
     * "ferias". `Str::ascii()` faz a transliteração dos acentos latinos
     * preservando 1 caractere pra 1 caractere (confirmado pros acentos do
     * português), o que também permite reaproveitar as mesmas posições pra
     * destacar o trecho no texto original (ver {@see Destaque::html()}).
     */
    public static function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii($texto));
    }

    /**
     * @return array<int, string>
     */
    public static function palavras(string $busca): array
    {
        return collect(preg_split('/\s+/u', trim($busca)))
            ->filter(fn (string $palavra) => $palavra !== '')
            ->values()
            ->all();
    }

    /**
     * @param  string|array<int, ?string>  $textos  Um texto único, ou vários
     *                                              (ex.: nome + requisitos)
     *                                              tratados como um conjunto —
     *                                              cada palavra da busca só
     *                                              precisa aparecer em algum
     *                                              deles, não necessariamente
     *                                              no mesmo.
     */
    public static function contemTodasAsPalavras(string|array $textos, string $busca): bool
    {
        $palavras = self::palavras($busca);

        if ($palavras === []) {
            return true;
        }

        $combinado = self::normalizar(is_array($textos) ? implode(' ', array_filter($textos)) : $textos);

        return collect($palavras)->every(fn (string $palavra) => str_contains($combinado, self::normalizar($palavra)));
    }
}
