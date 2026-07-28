<?php

namespace App\Filament\Resources\CompetenciaAntigas\Tables;

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\Ramo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompetenciaAntigasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label('Competência')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('areaDesenvolvimento.nome')
                    ->label('Área de Desenvolvimento'),
                TextColumn::make('areaDesenvolvimento.ramo.nome')
                    ->label('Ramo'),
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
