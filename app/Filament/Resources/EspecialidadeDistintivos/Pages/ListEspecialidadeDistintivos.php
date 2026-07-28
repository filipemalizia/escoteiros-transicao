<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Pages;

use App\Filament\Resources\EspecialidadeDistintivos\EspecialidadeDistintivoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEspecialidadeDistintivos extends ListRecords
{
    protected static string $resource = EspecialidadeDistintivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
