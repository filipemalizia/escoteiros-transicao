<?php

namespace App\Filament\Resources\Equivalencias\Pages;

use App\Filament\Resources\Equivalencias\EquivalenciaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquivalencias extends ListRecords
{
    protected static string $resource = EquivalenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
