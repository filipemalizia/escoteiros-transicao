<?php

namespace App\Filament\Resources\CompetenciaAntigas\Tables;

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\CompetenciaAntiga;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CompetenciaAntigasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('itens'))
            ->columns([
                TextColumn::make('descricao')
                    ->label('Competência')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('areaDesenvolvimento.nome')
                    ->label('Área de Desenvolvimento'),
                TextColumn::make('areaDesenvolvimento.ramo.nome')
                    ->label('Ramo'),
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
                SelectFilter::make('area_desenvolvimento_id')
                    ->label('Área de Desenvolvimento')
                    ->options(fn () => AreaDesenvolvimentoAntiga::pluck('nome', 'id')),
                SelectFilter::make('ramo')
                    ->label('Ramo')
                    ->relationship('areaDesenvolvimento.ramo', 'nome'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains(fn (CompetenciaAntiga $competencia) => $competencia->possuiItensComDadosVinculados())) {
                                Notification::make()
                                    ->title('Não é possível excluir uma ou mais competências selecionadas')
                                    ->body('Existem itens com progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a alguma das competências selecionadas. Remova essas dependências antes de excluir.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }
}
