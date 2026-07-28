<?php

namespace App\Services\Importacao;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RawSheetImport implements ToCollection
{
    public Collection $linhas;

    public function collection(Collection $linhas): void
    {
        $this->linhas = $linhas;
    }
}
