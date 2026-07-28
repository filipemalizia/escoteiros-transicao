<?php

namespace App\Filament\Resources\Jovens\Pages;

use App\Filament\Resources\Jovens\Concerns\InteractsWithRequisitosComplementares;
use App\Filament\Resources\Jovens\JovemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJovem extends CreateRecord
{
    use InteractsWithRequisitosComplementares;

    protected static string $resource = JovemResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extrairRequisitosComplementares($data);
    }

    protected function afterCreate(): void
    {
        $this->persistirRequisitosComplementares();
    }
}
