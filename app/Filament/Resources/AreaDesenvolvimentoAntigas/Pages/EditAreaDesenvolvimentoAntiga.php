<?php

namespace App\Filament\Resources\AreaDesenvolvimentoAntigas\Pages;

use App\Filament\Resources\AreaDesenvolvimentoAntigas\AreaDesenvolvimentoAntigaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAreaDesenvolvimentoAntiga extends EditRecord
{
    protected static string $resource = AreaDesenvolvimentoAntigaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
