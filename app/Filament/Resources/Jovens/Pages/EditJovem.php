<?php

namespace App\Filament\Resources\Jovens\Pages;

use App\Filament\Resources\Jovens\JovemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJovem extends EditRecord
{
    protected static string $resource = JovemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
