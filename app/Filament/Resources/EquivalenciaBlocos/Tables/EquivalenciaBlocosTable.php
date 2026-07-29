<?php

namespace App\Filament\Resources\EquivalenciaBlocos\Tables;

use App\Models\Ramo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EquivalenciaBlocosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('itemAntigo.codigo')
                    ->label('Item Antigo')
                    ->description(fn ($record) => $record->itemAntigo?->descricao)
                    ->searchable(),
                TextColumn::make('blocoNovo.titulo')
                    ->label('Bloco Novo')
                    ->description(fn ($record) => $record->blocoNovo?->eixo?->nome)
                    ->searchable(),
                TextColumn::make('observacao')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('ramo')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id'))
                    ->query(function (Builder $query, array $data) {
                        $ramoId = $data['value'] ?? null;

                        if (blank($ramoId)) {
                            return $query;
                        }

                        return $query->where(
                            fn (Builder $query) => $query
                                ->whereHas(
                                    'itemAntigo.competencia.areaDesenvolvimento',
                                    fn (Builder $query) => $query->where('ramo_id', $ramoId)
                                )
                                ->orWhereHas(
                                    'blocoNovo.eixo',
                                    fn (Builder $query) => $query->where('ramo_id', $ramoId)
                                )
                        );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
