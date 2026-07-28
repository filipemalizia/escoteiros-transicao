<?php

namespace App\Filament\Resources\BlocoNovos\Tables;

use App\Models\EixoNovo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlocoNovosTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
