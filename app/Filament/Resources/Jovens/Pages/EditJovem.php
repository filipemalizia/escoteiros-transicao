<?php

namespace App\Filament\Resources\Jovens\Pages;

use App\Filament\Resources\Jovens\Concerns\InteractsWithRequisitosComplementares;
use App\Filament\Resources\Jovens\JovemResource;
use App\Services\EtapaProgressaoService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJovem extends EditRecord
{
    use InteractsWithRequisitosComplementares;

    protected static string $resource = JovemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $ramoNome = $this->record->ramoAtual->nome;
        $service = new EtapaProgressaoService;

        $valoresAtuais = $this->record->requisitosComplementares->mapWithKeys(
            fn ($requisito) => [
                $requisito->chave => $requisito->tipo === 'contador'
                    ? $requisito->valor_numero
                    : $requisito->valor_booleano,
            ]
        );

        $data['requisitos_antigo'] = collect($service->chavesComplementaresAntigo($ramoNome))
            ->pluck('chave')
            ->mapWithKeys(fn ($chave) => [$chave => $valoresAtuais->get($chave)])
            ->all();

        $data['requisitos_novo'] = collect($service->chavesComplementaresNovo($ramoNome))
            ->pluck('chave')
            ->mapWithKeys(fn ($chave) => [$chave => $valoresAtuais->get($chave)])
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extrairRequisitosComplementares($data);
    }

    protected function afterSave(): void
    {
        $this->persistirRequisitosComplementares();
    }
}
