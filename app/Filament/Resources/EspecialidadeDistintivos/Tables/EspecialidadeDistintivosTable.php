<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EspecialidadeDistintivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('eixosNovos.ramo.nome')
                    ->label('Ramos')
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
