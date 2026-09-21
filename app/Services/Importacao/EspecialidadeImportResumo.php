<?php

namespace App\Services\Importacao;

class EspecialidadeImportResumo
{
    public int $criadas = 0;

    public int $atualizadas = 0;

    /**
     * Registros que já existiam (criados pelo importador de planilha antigo,
     * casando só por nome+tipo, sem fonte_specialty_id) e foram vinculados
     * ao invés de duplicados.
     */
    public int $adotadas = 0;

    public int $eixosNovosCriados = 0;

    public int $imagensBaixadas = 0;

    public int $imagensPuladas = 0;

    public int $imagensComErro = 0;

    /**
     * Contagem de imagens que seriam baixadas — só preenchido em modo
     * --dry-run (o download real é pulado de propósito, pois o arquivo em
     * disco não seria desfeito por um rollback de transação).
     */
    public int $imagensSimuladas = 0;

    /** @var array<int, array{id: int|string, nome: string, motivo: string}> */
    public array $erros = [];

    public function registrarErro(int|string $id, string $nome, string $motivo): void
    {
        $this->erros[] = ['id' => $id, 'nome' => $nome, 'motivo' => $motivo];
    }

    public function total(): int
    {
        return $this->criadas + $this->atualizadas + count($this->erros);
    }
}
