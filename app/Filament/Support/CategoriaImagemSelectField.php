<?php

namespace App\Filament\Support;

use App\Models\CategoriaImagem;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoriaImagemSelectField
{
    /**
     * Select de imagem compartilhada entre ramos (ex.: o eixo "Meio Ambiente"
     * existe uma vez por ramo, mas usa a mesma imagem nas 4). Permite
     * escolher uma categoria existente ou criar uma nova (com upload) sem
     * sair do form.
     */
    public static function make(string $tipo, string $label, string $chaveLabel): Select
    {
        return Select::make('categoria_imagem_id')
            ->label($label)
            ->relationship(
                name: 'categoriaImagem',
                titleAttribute: 'chave',
                modifyQueryUsing: fn (Builder $query) => $query->where('tipo', $tipo),
            )
            ->searchable()
            ->preload()
            ->helperText('Procure pelo nome antes de criar uma nova. Itens com o mesmo nome em ramos diferentes devem usar a mesma imagem.')
            ->createOptionForm([
                Hidden::make('tipo')->default($tipo),
                TextInput::make('chave')
                    ->label($chaveLabel)
                    ->required(),
                ...ImagemUploadField::make('imagem', 'Imagem'),
            ])
            ->createOptionUsing(function (array $data, Schema $schema) use ($tipo): int {
                $categoria = CategoriaImagem::create([
                    'tipo' => $tipo,
                    'chave' => $data['chave'],
                ]);

                // SpatieMediaLibraryFileUpload é sempre dehydrated(false) —
                // o upload não vem em $data, precisa disparar o save de
                // relacionamento manualmente contra o registro recém-criado.
                $schema->model($categoria)->saveRelationships();

                return $categoria->getKey();
            });
    }
}
