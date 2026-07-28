<?php

namespace App\Filament\Pages;

use App\Models\BlocoNovo;
use App\Models\EquivalenciaBloco;
use App\Models\ItemAntigo;
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

class EquivalenciaBlocoEmLote extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static ?string $navigationLabel = 'Equivalência de Bloco em Lote';

    protected static ?string $title = 'Equivalência de Bloco em Lote';

    protected string $view = 'filament.pages.equivalencia-bloco-em-lote';

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

                Select::make('bloco_novo_id')
                    ->label('Bloco (Programa Novo)')
                    ->visible(fn (Get $get) => filled($get('ramo_id')))
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesBlocos($get('ramo_id'), $search))
                    ->getOptionLabelUsing(fn ($value) => static::labelBloco($value)),

                Select::make('itens_antigo_ids')
                    ->label('Itens do Programa Antigo que contam como Ação Variável deste bloco')
                    ->multiple()
                    ->visible(fn (Get $get) => filled($get('ramo_id')))
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesItensAntigos($get('ramo_id'), $search))
                    ->getOptionLabelsUsing(fn (array $values) => collect($values)
                        ->mapWithKeys(fn ($value) => [$value => static::labelItemAntigo($value)])),

                Textarea::make('observacao')
                    ->label('Observação (aplicada a todos os vínculos criados neste lote)')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesBlocos(?int $ramoId, string $search): array
    {
        if (blank($ramoId)) {
            return [];
        }

        return BlocoNovo::query()
            ->whereHas('eixo', fn ($query) => $query->where('ramo_id', $ramoId))
            ->where('titulo', 'like', "%{$search}%")
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (BlocoNovo $bloco) => [$bloco->id => static::labelBloco($bloco->id, $bloco)])
            ->all();
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

    protected static function labelBloco(mixed $value, ?BlocoNovo $bloco = null): string
    {
        $bloco ??= BlocoNovo::with('eixo')->find($value);

        if (! $bloco) {
            return (string) $value;
        }

        return "{$bloco->eixo->nome} — {$bloco->titulo}";
    }

    protected static function labelItemAntigo(mixed $value, ?ItemAntigo $item = null): string
    {
        $item ??= ItemAntigo::find($value);

        if (! $item) {
            return (string) $value;
        }

        return "{$item->codigo}: ".Str::limit($item->descricao, 50);
    }

    public function criar(): void
    {
        $data = $this->form->getState();

        $blocoNovoId = $data['bloco_novo_id'];
        $observacao = $data['observacao'] ?? null;

        $criadas = 0;
        $ignoradas = 0;

        DB::transaction(function () use ($data, $blocoNovoId, $observacao, &$criadas, &$ignoradas) {
            foreach ($data['itens_antigo_ids'] ?? [] as $itemAntigoId) {
                $existe = EquivalenciaBloco::query()
                    ->where('item_antigo_id', $itemAntigoId)
                    ->where('bloco_novo_id', $blocoNovoId)
                    ->exists();

                if ($existe) {
                    $ignoradas++;

                    continue;
                }

                EquivalenciaBloco::create([
                    'item_antigo_id' => $itemAntigoId,
                    'bloco_novo_id' => $blocoNovoId,
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
