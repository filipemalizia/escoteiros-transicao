<?php

namespace App\Services\Importacao;

class ImportResumo
{
    public int $gruposCriados = 0;

    public int $subgruposCriados = 0;

    public int $itensCriados = 0;

    public int $itensIgnorados = 0;

    /** @var array<int, array{linha: int, motivo: string}> */
    public array $erros = [];

    /** @var array<int, array{linha: int, motivo: string}> */
    public array $avisos = [];

    public function registrarErro(int $linha, string $motivo): void
    {
        $this->itensIgnorados++;
        $this->erros[] = ['linha' => $linha, 'motivo' => $motivo];
    }

    public function registrarAviso(int $linha, string $motivo): void
    {
        $this->avisos[] = ['linha' => $linha, 'motivo' => $motivo];
    }

    /**
     * @return array{gruposCriados: int, subgruposCriados: int, itensCriados: int, itensIgnorados: int, erros: array, avisos: array}
     */
    public function toArray(): array
    {
        return [
            'gruposCriados' => $this->gruposCriados,
            'subgruposCriados' => $this->subgruposCriados,
            'itensCriados' => $this->itensCriados,
            'itensIgnorados' => $this->itensIgnorados,
            'erros' => $this->erros,
            'avisos' => $this->avisos,
        ];
    }
}
