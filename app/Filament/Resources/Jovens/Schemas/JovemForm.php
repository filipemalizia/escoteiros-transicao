<?php

namespace App\Filament\Resources\Jovens\Schemas;

use App\Models\Equipe;
use App\Models\Ramo;
use App\Services\EtapaProgressaoService;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class JovemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required(),
                TextInput::make('registro')
                    ->label('Registro Escoteiro')
                    ->numeric()
                    ->unique(ignoreRecord: true)
                    ->helperText('Usado pelo jovem pra acessar o portal público junto com a data de nascimento.'),
                DatePicker::make('data_nascimento')
                    ->required(),
                Select::make('ramo_atual_id')
                    ->label('Ramo')
                    ->relationship('ramoAtual', 'nome')
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (Set $set) => $set('equipe_id', null)),

                Select::make('equipe_id')
                    ->label('Equipe')
                    ->relationship(
                        name: 'equipe',
                        titleAttribute: 'nome',
                        modifyQueryUsing: fn (Builder $query, Get $get) => static::equipesPermitidas($query, $get('ramo_atual_id')),
                    )
                    ->visible(fn (Get $get) => filled($get('ramo_atual_id')))
                    // O filtro acima só restringe as opções exibidas no
                    // dropdown — o Filament não revalida isso ao salvar um
                    // Select de relationship (`Select::saveStateToRelationship()`
                    // simplesmente associa o valor recebido), então uma
                    // requisição adulterada poderia setar qualquer equipe_id
                    // do sistema. Esta regra fecha essa brecha, reaplicando a
                    // mesma restrição no servidor.
                    ->rule(fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                        if (blank($value)) {
                            return;
                        }

                        $permitido = static::equipesPermitidas(Equipe::query(), $get('ramo_atual_id'))
                            ->whereKey($value)
                            ->exists();

                        if (! $permitido) {
                            $fail('Você não tem permissão pra atribuir essa equipe.');
                        }
                    })
                    ->nullable(),

                Section::make('Requisitos Complementares')
                    ->description('Requisitos que não vêm do checklist de Itens (contadores, insígnias, recomendações).')
                    ->visible(fn (Get $get) => filled($get('ramo_atual_id')))
                    ->schema(fn (Get $get) => static::secoesRequisitos($get('ramo_atual_id')))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Equipes do ramo informado que o usuário logado pode atribuir a um
     * jovem — admin vê todas, chefe comum só as próprias. Usada tanto pra
     * montar as opções do Select quanto pra revalidar no servidor o que foi
     * de fato submetido (ver o `->rule()` do campo `equipe_id` acima).
     */
    protected static function equipesPermitidas(Builder $query, ?int $ramoId): Builder
    {
        $query->where('ramo_id', $ramoId);

        if (! auth()->user()?->isAdmin()) {
            $query->whereIn('id', auth()->user()?->equipes()->pluck('equipes.id') ?? []);
        }

        return $query;
    }

    /**
     * @return array<int, Component>
     */
    protected static function secoesRequisitos(?int $ramoId): array
    {
        $ramo = blank($ramoId) ? null : Ramo::find($ramoId);

        if (! $ramo) {
            return [];
        }

        $service = new EtapaProgressaoService;

        return [
            Section::make('Programa Antigo')
                ->schema(static::camposParaChaves($service->chavesComplementaresAntigo($ramo->nome), 'antigo'))
                ->columns(2),
            Section::make('Programa Novo')
                ->schema(static::camposParaChaves($service->chavesComplementaresNovo($ramo->nome), 'novo'))
                ->columns(2),
        ];
    }

    /**
     * @param  array<int, array{chave: string, tipo: string, label: string, meta?: int}>  $chaves
     * @return array<int, Component>
     */
    protected static function camposParaChaves(array $chaves, string $sistema): array
    {
        return array_map(
            fn (array $definicao) => $definicao['tipo'] === 'contador'
                ? TextInput::make("requisitos_{$sistema}.{$definicao['chave']}")
                    ->label($definicao['label'].(isset($definicao['meta']) ? " (meta: {$definicao['meta']})" : ''))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                : Toggle::make("requisitos_{$sistema}.{$definicao['chave']}")
                    ->label($definicao['label']),
            $chaves
        );
    }
}
