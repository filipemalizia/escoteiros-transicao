<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Tables;

use App\Models\EspecialidadeDistintivo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EspecialidadeDistintivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['eixosNovos.ramo', 'ramos']))
            ->columns([
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('tipo')
                    ->badge(),
                TextColumn::make('estrutura')
                    ->badge()
                    ->placeholder('Sem estrutura')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'itens_niveis' => 'Itens + níveis',
                        'atividades_temas' => 'Atividades + temas',
                        default => null,
                    }),
                TextColumn::make('ramos_exibicao')
                    ->label('Ramos')
                    ->state(fn (EspecialidadeDistintivo $record) => $record->eixosNovos->pluck('ramo.nome')
                        ->merge($record->ramos->pluck('nome'))
                        ->unique()
                        ->sort()
                        ->values())
                    ->badge()
                    ->listWithLineBreaks(),
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
                SelectFilter::make('tipo')
                    ->options([
                        'Especialidade' => 'Especialidade',
                        'Insígnia' => 'Insígnia',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('Ao excluir, a imagem e todos os itens vinculados às especialidades/insígnias selecionadas também serão excluídos. Essa ação não pode ser desfeita.')
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains(fn (EspecialidadeDistintivo $especialidade) => $especialidade->possuiItensComDadosVinculados())) {
                                Notification::make()
                                    ->title('Não é possível excluir uma ou mais especialidades/insígnias selecionadas')
                                    ->body('Existem itens com progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a alguma delas. Remova essas dependências antes de excluir.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }
}
