<?php

namespace App\Filament\Resources\BlocoNovos\Tables;

use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class BlocoNovosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('itens'))
            ->columns([
                TextColumn::make('titulo')
                    ->searchable(),
                TextColumn::make('eixo.nome')
                    ->label('Eixo'),
                TextColumn::make('eixo.ramo.nome')
                    ->label('Ramo'),
                TextColumn::make('quantidade_minima_variaveis')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('itens_count')
                    ->label('Itens')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('eixo_id')
                    ->label('Eixo')
                    ->options(fn () => EixoNovo::pluck('nome', 'id')),
                SelectFilter::make('ramo')
                    ->label('Ramo')
                    ->relationship('eixo.ramo', 'nome'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains(fn (BlocoNovo $bloco) => $bloco->possuiItensComDadosVinculados())) {
                                Notification::make()
                                    ->title('Não é possível excluir um ou mais blocos selecionados')
                                    ->body('Existem itens com progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a algum dos blocos selecionados. Remova essas dependências antes de excluir.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }
}
