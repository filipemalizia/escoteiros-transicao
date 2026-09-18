<?php

namespace App\Filament\Support;

use App\Models\CategoriaImagem;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class AtribuirImagemBulkAction
{
    /**
     * Ação em massa pra atribuir uma `CategoriaImagem` a vários Eixos/Blocos
     * de uma vez — ex.: selecionar as 4 linhas "Meio Ambiente", uma por
     * ramo, e apontar todas pra mesma imagem, sem abrir cada registro
     * individualmente. Deixa escolher uma categoria já existente OU criar
     * uma nova com upload direto aqui.
     *
     * Não usa `Select::createOptionForm()` de propósito: esse combo dentro
     * do schema de um `BulkAction` bate num bug do próprio Filament
     * (`hasRelationship()`/`getRelationship()` chamado num contexto sem
     * registro único, já que a ação opera sobre uma `Collection`) —
     * confirmado com um script isolado reproduzindo o erro fora dos testes
     * também, não é só artefato de teste. Em vez disso, o upload de imagem
     * nova é um campo próprio no schema da ação (`FileUpload` puro, não
     * `SpatieMediaLibraryFileUpload` — esse também depende de um `$record`
     * único pra `saveRelationships()`), e o `action()` cria a categoria e
     * anexa a mídia manualmente.
     */
    public static function make(string $tipo, string $chaveLabel): BulkAction
    {
        return BulkAction::make('atribuirImagem')
            ->label('Atribuir imagem')
            ->icon('heroicon-o-photo')
            ->schema([
                Select::make('categoria_imagem_id')
                    ->label('Categoria de imagem existente')
                    ->options(fn () => CategoriaImagem::query()->where('tipo', $tipo)->pluck('chave', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText('Ou deixe em branco e preencha os campos abaixo pra criar uma nova.'),
                TextInput::make('nova_chave')
                    ->label($chaveLabel)
                    ->visible(fn (Get $get) => blank($get('categoria_imagem_id')))
                    ->requiredWithout('categoria_imagem_id'),
                FileUpload::make('nova_imagem')
                    ->label('Imagem')
                    ->image()
                    ->disk('public')
                    ->visible(fn (Get $get) => blank($get('categoria_imagem_id'))),
            ])
            ->action(function (Collection $records, array $data) use ($tipo): void {
                $categoriaId = $data['categoria_imagem_id'] ?? null;

                if (! $categoriaId) {
                    $categoria = CategoriaImagem::create([
                        'tipo' => $tipo,
                        'chave' => $data['nova_chave'],
                    ]);

                    if (filled($data['nova_imagem'] ?? null)) {
                        $categoria->addMedia(Storage::disk('public')->path($data['nova_imagem']))
                            ->toMediaCollection('imagem');
                    }

                    $categoriaId = $categoria->id;
                }

                $records->each->update(['categoria_imagem_id' => $categoriaId]);

                Notification::make()
                    ->title('Imagem atribuída a '.$records->count().' registro(s)')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
