<?php

namespace App\Filament\Resources\Jovens\Pages;

use App\Filament\Resources\Jovens\JovemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJovens extends ListRecords
{
    protected static string $resource = JovemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
