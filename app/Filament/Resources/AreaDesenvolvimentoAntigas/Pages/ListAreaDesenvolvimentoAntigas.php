<?php

namespace App\Filament\Resources\AreaDesenvolvimentoAntigas\Pages;

use App\Filament\Resources\AreaDesenvolvimentoAntigas\AreaDesenvolvimentoAntigaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAreaDesenvolvimentoAntigas extends ListRecords
{
    protected static string $resource = AreaDesenvolvimentoAntigaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
