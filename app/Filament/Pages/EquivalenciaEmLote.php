<?php

namespace App\Filament\Pages;

use App\Models\Equivalencia;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\Ramo;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EquivalenciaEmLote extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Nova Equivalência em Lote';

    protected static ?string $title = 'Nova Equivalência em Lote';

    protected string $view = 'filament.pages.equivalencia-em-lote';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('ramo_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::pluck('nome', 'id'))
                    ->live()
                    ->required(),

                Select::make('tipo_equivalencia')
                    ->label('Tipo de Equivalência')
                    ->options([
                        '1-1' => '1 para 1',
                        'N-1' => 'N para 1 (vários antigos → um novo)',
                        '1-N' => '1 para N (um antigo → vários novos)',
                    ])
                    ->live()
                    ->required(),

                Select::make('item_antigo_id')
                    ->label('Item Antigo')
                    ->visible(fn (Get $get) => in_array($get('tipo_equivalencia'), ['1-1', '1-N']))
                    ->required(fn (Get $get) => in_array($get('tipo_equivalencia'), ['1-1', '1-N']))
                    ->searchable()
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesItensAntigos($get('ramo_id'), $search))
                    ->getOptionLabelUsing(fn ($value) => static::labelItemAntigo($value)),

                Select::make('itens_antigo_ids')
                    ->label('Itens Antigos')
                    ->multiple()
                    ->visible(fn (Get $get) => $get('tipo_equivalencia') === 'N-1')
                    ->required(fn (Get $get) => $get('tipo_equivalencia') === 'N-1')
                    ->searchable()
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesItensAntigos($get('ramo_id'), $search))
                    ->getOptionLabelsUsing(fn (array $values) => collect($values)
                        ->mapWithKeys(fn ($value) => [$value => static::labelItemAntigo($value)])),

                Select::make('item_novo_id')
                    ->label('Item Novo')
                    ->visible(fn (Get $get) => in_array($get('tipo_equivalencia'), ['1-1', 'N-1']))
                    ->required(fn (Get $get) => in_array($get('tipo_equivalencia'), ['1-1', 'N-1']))
                    ->searchable()
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesItensNovos($get('ramo_id'), $search))
                    ->getOptionLabelUsing(fn ($value) => static::labelItemNovo($value)),

                Select::make('itens_novo_ids')
                    ->label('Itens Novos')
                    ->multiple()
                    ->visible(fn (Get $get) => $get('tipo_equivalencia') === '1-N')
                    ->required(fn (Get $get) => $get('tipo_equivalencia') === '1-N')
                    ->searchable()
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesItensNovos($get('ramo_id'), $search))
                    ->getOptionLabelsUsing(fn (array $values) => collect($values)
                        ->mapWithKeys(fn ($value) => [$value => static::labelItemNovo($value)])),

                Textarea::make('observacao')
                    ->label('Observação (aplicada a todas as linhas criadas neste lote)')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesItensAntigos(?int $ramoId, string $search): array
    {
        if (blank($ramoId)) {
            return [];
        }

        return ItemAntigo::query()
            ->whereHas('competencia.areaDesenvolvimento', fn ($query) => $query->where('ramo_id', $ramoId))
            ->where(fn ($query) => $query
                ->where('codigo', 'like', "%{$search}%")
                ->orWhere('descricao', 'like', "%{$search}%"))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (ItemAntigo $item) => [$item->id => static::labelItemAntigo($item->id, $item)])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesItensNovos(?int $ramoId, string $search): array
    {
        if (blank($ramoId)) {
            return [];
        }

        return ItemNovo::query()
            ->whereHas('bloco.eixo', fn ($query) => $query->where('ramo_id', $ramoId))
            ->where(fn ($query) => $query
                ->where('codigo', 'like', "%{$search}%")
                ->orWhere('descricao', 'like', "%{$search}%"))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (ItemNovo $item) => [$item->id => static::labelItemNovo($item->id, $item)])
            ->all();
    }

    protected static function labelItemAntigo(mixed $value, ?ItemAntigo $item = null): string
    {
        $item ??= ItemAntigo::find($value);

        if (! $item) {
            return (string) $value;
        }

        return "{$item->codigo}: ".Str::limit($item->descricao, 50);
    }

    protected static function labelItemNovo(mixed $value, ?ItemNovo $item = null): string
    {
        $item ??= ItemNovo::find($value);

        if (! $item) {
            return (string) $value;
        }

        return "{$item->codigo}: ".Str::limit($item->descricao, 50);
    }

    public function criar(): void
    {
        $data = $this->form->getState();

        $tipo = $data['tipo_equivalencia'];
        $observacao = $data['observacao'] ?? null;

        $pares = match ($tipo) {
            '1-1' => [[$data['item_antigo_id'], $data['item_novo_id']]],
            'N-1' => collect($data['itens_antigo_ids'] ?? [])
                ->map(fn ($antigoId) => [$antigoId, $data['item_novo_id']])
                ->all(),
            '1-N' => collect($data['itens_novo_ids'] ?? [])
                ->map(fn ($novoId) => [$data['item_antigo_id'], $novoId])
                ->all(),
            default => [],
        };

        $criadas = 0;
        $ignoradas = 0;

        DB::transaction(function () use ($pares, $tipo, $observacao, &$criadas, &$ignoradas) {
            foreach ($pares as [$itemAntigoId, $itemNovoId]) {
                $existe = Equivalencia::query()
                    ->where('item_antigo_id', $itemAntigoId)
                    ->where('item_novo_id', $itemNovoId)
                    ->exists();

                if ($existe) {
                    $ignoradas++;

                    continue;
                }

                Equivalencia::create([
                    'item_antigo_id' => $itemAntigoId,
                    'item_novo_id' => $itemNovoId,
                    'tipo_equivalencia' => $tipo,
                    'observacao' => $observacao,
                ]);

                $criadas++;
            }
        });

        Notification::make()
            ->title("{$criadas} criada(s), {$ignoradas} já existia(m) e foram ignoradas.")
            ->success()
            ->send();

        $this->form->fill();
    }
}
