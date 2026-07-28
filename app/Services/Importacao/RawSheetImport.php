<?php

namespace App\Services\Importacao;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class RawSheetImport implements ToCollection, WithCustomCsvSettings
{
    public Collection $linhas;

    protected ?string $caminhoArquivo = null;

    public function collection(Collection $linhas): void
    {
        $this->linhas = $linhas;
    }

    /**
     * Usado só para arquivos .csv, pra detectar delimitador e encoding
     * automaticamente (planilhas exportadas em pt-BR costumam vir com
     * ';' e/ou Windows-1252).
     */
    public function setCaminhoArquivo(string $caminho): void
    {
        $this->caminhoArquivo = $caminho;
    }

    /**
     * @return array<string, string>
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->detectarDelimitador(),
            'input_encoding' => $this->detectarEncoding(),
        ];
    }

    protected function detectarDelimitador(): string
    {
        $primeiraLinha = $this->lerPrimeiraLinha();

        if ($primeiraLinha === null) {
            return ',';
        }

        return substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';
    }

    protected function detectarEncoding(): string
    {
        if ($this->caminhoArquivo === null) {
            return 'UTF-8';
        }

        $conteudo = file_get_contents($this->caminhoArquivo);

        if ($conteudo === false || $conteudo === '') {
            return 'UTF-8';
        }

        // Verifica validade de UTF-8 primeiro (determinístico) antes de usar
        // mb_detect_encoding (heurística, que erra fácil com Windows-1252 em
        // conteúdo que na verdade é UTF-8 válido — já vimos isso na prática).
        if (mb_check_encoding($conteudo, 'UTF-8')) {
            return 'UTF-8';
        }

        $detectado = mb_detect_encoding($conteudo, ['Windows-1252', 'ISO-8859-1'], true);

        return $detectado ?: 'UTF-8';
    }

    protected function lerPrimeiraLinha(): ?string
    {
        if ($this->caminhoArquivo === null) {
            return null;
        }

        $handle = fopen($this->caminhoArquivo, 'r');

        if ($handle === false) {
            return null;
        }

        $linha = fgets($handle);
        fclose($handle);

        return $linha === false ? null : $linha;
    }
}
