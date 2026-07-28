<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Pages;

use App\Filament\Resources\EspecialidadeDistintivos\EspecialidadeDistintivoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEspecialidadeDistintivo extends EditRecord
{
    protected static string $resource = EspecialidadeDistintivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
