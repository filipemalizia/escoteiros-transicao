<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Pages;

use App\Filament\Resources\EspecialidadeDistintivos\EspecialidadeDistintivoResource;
use App\Models\EspecialidadeDistintivo;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEspecialidadeDistintivo extends EditRecord
{
    protected static string $resource = EspecialidadeDistintivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('Ao excluir, a imagem e todos os itens vinculados a esta especialidade/insígnia também serão excluídos. Essa ação não pode ser desfeita.')
                ->before(function (EspecialidadeDistintivo $record, DeleteAction $action) {
                    if ($record->possuiItensComDadosVinculados()) {
                        Notification::make()
                            ->title('Não é possível excluir esta especialidade/insígnia')
                            ->body('Existem itens com progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a ela. Remova essas dependências antes de excluir.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
