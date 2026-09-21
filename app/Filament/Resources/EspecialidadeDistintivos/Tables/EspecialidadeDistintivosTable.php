<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Tables;

use App\Models\EspecialidadeDistintivo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EspecialidadeDistintivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['eixosNovos.ramo', 'ramos'])->withExists([
                'media as tem_imagem_nivel_1' => fn (Builder $q) => $q->where('collection_name', 'imagem_nivel_1'),
            ]))
            ->columns([
                IconColumn::make('sem_imagem')
                    ->label('')
                    ->getStateUsing(fn (EspecialidadeDistintivo $record) => ! $record->tem_imagem_nivel_1)
                    ->icon(fn (bool $state) => $state ? Heroicon::OutlinedExclamationTriangle : null)
                    ->color('warning')
                    ->tooltip(fn (bool $state) => $state ? 'Sem imagem cadastrada — não aparece colorida no portal/cartão de conquista' : null),
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
                Filter::make('sem_imagem')
                    ->label('Sem imagem')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereDoesntHave(
                        'media',
                        fn (Builder $q) => $q->where('collection_name', 'imagem_nivel_1'),
                    )),
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
