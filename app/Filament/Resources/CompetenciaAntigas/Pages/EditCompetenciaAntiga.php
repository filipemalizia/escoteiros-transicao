<?php

namespace App\Filament\Resources\CompetenciaAntigas\Pages;

use App\Filament\Resources\CompetenciaAntigas\CompetenciaAntigaResource;
use App\Models\CompetenciaAntiga;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompetenciaAntiga extends EditRecord
{
    protected static string $resource = CompetenciaAntigaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (CompetenciaAntiga $record, DeleteAction $action) {
                    if ($record->possuiItensComDadosVinculados()) {
                        Notification::make()
                            ->title('Não é possível excluir esta competência')
                            ->body('Existem itens com progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a esta competência. Remova essas dependências antes de excluir.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
