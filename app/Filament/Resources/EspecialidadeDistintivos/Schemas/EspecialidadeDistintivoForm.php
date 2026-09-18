<?php

namespace App\Filament\Resources\EspecialidadeDistintivos\Schemas;

use App\Filament\Support\ImagemUploadField;
use App\Models\EixoNovo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EspecialidadeDistintivoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required(),
                Select::make('tipo')
                    ->options(['Especialidade' => 'Especialidade', 'Insígnia' => 'Insígnia'])
                    ->live()
                    ->required(),
                Select::make('modalidade')
                    ->label('Modalidade')
                    ->options([
                        'Geral' => 'Geral',
                        'Ar' => 'Ar',
                        'Mar' => 'Mar',
                    ])
                    ->default('Geral')
                    ->visible(fn (Get $get) => $get('tipo') === 'Insígnia')
                    ->helperText('"Geral" aparece pra todo mundo, independente da modalidade do jovem. Só faz sentido pra Insígnia — Especialidade nunca é específica de modalidade.'),
                Textarea::make('descricao')
                    ->columnSpanFull(),
                Select::make('eixosNovos')
                    ->label('Ramo(s) / Eixo(s)')
                    ->relationship('eixosNovos', 'nome')
                    ->getOptionLabelFromRecordUsing(fn (EixoNovo $record) => "{$record->ramo->nome} / {$record->nome}")
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                Select::make('estrutura')
                    ->label('Estrutura')
                    ->options([
                        'itens_niveis' => 'Itens + níveis (Lobinho/Escoteiro)',
                        'atividades_temas' => 'Atividades + temas (Sênior/Pioneiro)',
                    ])
                    ->live()
                    ->required(),

                // Lobinho/Escoteiro
                Textarea::make('regra_niveis')
                    ->label('Regra de níveis (texto)')
                    ->visible(fn (Get $get) => $get('estrutura') === 'itens_niveis')
                    ->columnSpanFull(),
                TextInput::make('minimo_nivel_1')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('estrutura') === 'itens_niveis'),
                TextInput::make('minimo_nivel_2')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('estrutura') === 'itens_niveis'),

                // Sênior/Pioneiro
                TagsInput::make('sugestao_temas')
                    ->label('Sugestão de temas')
                    ->visible(fn (Get $get) => $get('estrutura') === 'atividades_temas')
                    ->columnSpanFull(),

                ...ImagemUploadField::make('imagem_nivel_1', 'Imagem nível 1'),
                ...array_map(
                    fn ($component) => $component->visible(fn (Get $get) => $get('estrutura') === 'itens_niveis'),
                    ImagemUploadField::make('imagem_nivel_2', 'Imagem nível 2'),
                ),

                TextInput::make('fonte_specialty_id')
                    ->label('ID original no PAXTU')
                    ->numeric()
                    ->helperText('Preenchido automaticamente pelo importador. Só edite manualmente se souber o que está fazendo.'),
            ]);
    }
}
