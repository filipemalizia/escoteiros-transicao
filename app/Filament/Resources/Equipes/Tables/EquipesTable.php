<?php

namespace App\Filament\Resources\Equipes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EquipesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('ramo.nome')
                    ->label('Ramo'),
                TextColumn::make('modalidade')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Básica' ? 'gray' : 'warning'),
                TextColumn::make('usuarios_count')
                    ->label('Responsáveis')
                    ->counts('usuarios'),
            ])
            ->filters([
                SelectFilter::make('modalidade')
                    ->options([
                        'Básica' => 'Básica',
                        'Ar' => 'Ar',
                        'Mar' => 'Mar',
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
