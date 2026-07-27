<?php

namespace App\Filament\Resources\Jovems\Pages;

use App\Filament\Resources\Jovems\JovemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJovems extends ListRecords
{
    protected static string $resource = JovemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
