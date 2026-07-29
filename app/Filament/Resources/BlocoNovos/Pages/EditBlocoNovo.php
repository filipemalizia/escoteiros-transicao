<?php

namespace App\Filament\Resources\BlocoNovos\Pages;

use App\Filament\Resources\BlocoNovos\BlocoNovoResource;
use App\Models\BlocoNovo;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBlocoNovo extends EditRecord
{
    protected static string $resource = BlocoNovoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (BlocoNovo $record, DeleteAction $action) {
                    if ($record->possuiItensComDadosVinculados()) {
                        Notification::make()
                            ->title('Não é possível excluir este bloco')
                            ->body('Existem itens com progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a este bloco. Remova essas dependências antes de excluir.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
