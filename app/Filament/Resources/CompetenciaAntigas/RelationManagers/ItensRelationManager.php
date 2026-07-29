<?php

namespace App\Filament\Resources\CompetenciaAntigas\RelationManagers;

use App\Models\ItemAntigo;
use App\Services\EtapaProgressaoService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ItensRelationManager extends RelationManager
{
    protected static string $relationship = 'itens';

    protected function ramoDaCompetencia(): string
    {
        return $this->getOwnerRecord()->areaDesenvolvimento->ramo->nome;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('etapa')
                    ->label('Piscina de etapas')
                    ->options(fn () => array_combine(
                        $etapas = EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia()),
                        $etapas,
                    ))
                    ->live()
                    ->visible(fn () => filled(EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia())))
                    ->required(fn () => filled(EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia()))),
                Toggle::make('introdutorio')
                    ->label('Item do Período Introdutório (obrigatório pra 1ª etapa da piscina)')
                    ->visible(fn (Get $get) => EtapaProgressaoService::poolUsaIntrodutorio($this->ramoDaCompetencia(), $get('etapa'))),
                Textarea::make('descricao')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
            ->columns([
                TextColumn::make('codigo')
                    ->searchable(),
                TextColumn::make('etapa')
                    ->label('Piscina de etapas')
                    ->visible(fn () => filled(EtapaProgressaoService::etapasAntigoPorRamo($this->ramoDaCompetencia()))),
                IconColumn::make('introdutorio')
                    ->label('Introdutório')
                    ->boolean()
                    ->visible(fn () => $this->ramoDaCompetencia() === 'Lobinho'),
                TextColumn::make('descricao')
                    ->limit(60),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (ItemAntigo $record, DeleteAction $action) {
                        if ($record->possuiDadosVinculados()) {
                            Notification::make()
                                ->title('Não é possível excluir este item')
                                ->body('Existe progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a este item. Remova essas dependências antes de excluir.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains(fn (ItemAntigo $item) => $item->possuiDadosVinculados())) {
                                Notification::make()
                                    ->title('Não é possível excluir um ou mais itens selecionados')
                                    ->body('Existe progresso registrado por algum jovem, ou equivalências cadastradas, vinculados a algum dos itens selecionados. Remova essas dependências antes de excluir.')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }
}
