<?php

namespace App\Filament\Resources\Jovens\Tables;

use App\Filament\Resources\Jovens\JovemResource;
use App\Models\Equipe;
use App\Models\Jovem;
use App\Models\Ramo;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JovensTable
{
    /**
     * Condição de "pendente de avaliação" usada nas três tabelas de
     * progresso — mesma regra do `EstatisticasOverview::statAvaliacoesPendentes()`.
     */
    private static function comItemPendente(Builder $query): Builder
    {
        return $query->where('solicitado_pelo_jovem', true)->where('concluido', false);
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withExists([
                'progressoAntigo as tem_pendencia_antigo' => fn (Builder $q) => self::comItemPendente($q),
                'progressoNovo as tem_pendencia_novo' => fn (Builder $q) => self::comItemPendente($q),
                'progressoPersonalizado as tem_pendencia_personalizado' => fn (Builder $q) => self::comItemPendente($q),
            ]))
            ->columns([
                IconColumn::make('pendencia_revisao')
                    ->label('')
                    ->getStateUsing(fn (Jovem $record) => $record->tem_pendencia_antigo || $record->tem_pendencia_novo || $record->tem_pendencia_personalizado)
                    ->icon(fn (bool $state) => $state ? Heroicon::OutlinedExclamationTriangle : null)
                    ->color('warning')
                    ->tooltip(fn (bool $state) => $state ? 'Possui itens aguardando avaliação' : null),
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('registro')
                    ->label('Registro')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('data_nascimento')
                    ->date()
                    ->label('Data de nascimento'),
                TextColumn::make('ramoAtual.nome')
                    ->label('Ramo atual'),
                TextColumn::make('equipe.nome')
                    ->label('Equipe')
                    ->placeholder('Sem equipe'),
            ])
            ->filters([
                SelectFilter::make('ramo_atual_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id')),
                SelectFilter::make('equipe_id')
                    ->label('Equipe')
                    ->options(fn () => Equipe::query()
                        ->when(
                            ! auth()->user()?->isAdmin(),
                            fn ($query) => $query->whereIn('id', auth()->user()?->equipes()->pluck('equipes.id') ?? [])
                        )
                        ->pluck('nome', 'id')),
            ])
            ->recordActions([
                Action::make('progresso')
                    ->label('Ver Progresso')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->url(fn (Jovem $record) => JovemResource::getUrl('progresso', ['record' => $record])),
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
