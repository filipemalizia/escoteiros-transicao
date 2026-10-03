<?php

namespace App\Filament\Pages;

use App\Models\EquivalenciaEspecialidade;
use App\Models\EspecialidadeDistintivo;
use App\Models\ItemNovo;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class EquivalenciaEspecialidadeEmLote extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static string|UnitEnum|null $navigationGroup = 'Ferramentas';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Equivalência de Especialidade em Lote';

    protected static ?string $title = 'Equivalência de Especialidade em Lote';

    protected string $view = 'filament.pages.equivalencia-especialidade-em-lote';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

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
                Select::make('item_novo_id')
                    ->label('Item (Novo)')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => static::opcoesItensNovos($search))
                    ->getOptionLabelUsing(fn ($value) => static::labelItemNovo($value)),

                Select::make('especialidade_distintivo_ids')
                    ->label('Especialidades/Insígnias que credita este item')
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => static::opcoesEspecialidades($search))
                    ->getOptionLabelsUsing(fn (array $values) => collect($values)
                        ->mapWithKeys(fn ($value) => [$value => static::labelEspecialidade($value)])),

                Select::make('nivel_minimo')
                    ->label('Nível mínimo exigido')
                    ->options([1 => 'Nível 1', 2 => 'Nível 2'])
                    ->helperText('Aplicado a todos os vínculos criados neste lote. Deixe em branco pra aceitar qualquer nível.'),

                Textarea::make('observacao')
                    ->label('Observação (aplicada a todos os vínculos criados neste lote)')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesItensNovos(string $search): array
    {
        return ItemNovo::query()
            ->where(fn ($query) => $query
                ->where('codigo', 'like', "%{$search}%")
                ->orWhere('descricao', 'like', "%{$search}%"))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (ItemNovo $item) => [$item->id => static::labelItemNovo($item->id, $item)])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesEspecialidades(string $search): array
    {
        return EspecialidadeDistintivo::query()
            ->where('nome', 'like', "%{$search}%")
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (EspecialidadeDistintivo $especialidade) => [$especialidade->id => static::labelEspecialidade($especialidade->id, $especialidade)])
            ->all();
    }

    protected static function labelItemNovo(mixed $value, ?ItemNovo $item = null): string
    {
        $item ??= ItemNovo::with('bloco.eixo.ramo')->find($value);

        if (! $item) {
            return (string) $value;
        }

        return sprintf(
            '%s / %s / %s / %s: %s',
            $item->bloco->eixo->ramo->nome,
            $item->bloco->eixo->nome,
            $item->bloco->titulo,
            $item->codigo,
            Str::limit($item->descricao, 40),
        );
    }

    protected static function labelEspecialidade(mixed $value, ?EspecialidadeDistintivo $especialidade = null): string
    {
        $especialidade ??= EspecialidadeDistintivo::find($value);

        if (! $especialidade) {
            return (string) $value;
        }

        return "{$especialidade->tipo}: {$especialidade->nome}";
    }

    public function criar(): void
    {
        $data = $this->form->getState();

        $itemNovoId = $data['item_novo_id'];
        $nivelMinimo = $data['nivel_minimo'] ?? null;
        $observacao = $data['observacao'] ?? null;

        $criadas = 0;
        $ignoradas = 0;

        DB::transaction(function () use ($data, $itemNovoId, $nivelMinimo, $observacao, &$criadas, &$ignoradas) {
            foreach ($data['especialidade_distintivo_ids'] ?? [] as $especialidadeDistintivoId) {
                $existe = EquivalenciaEspecialidade::query()
                    ->where('especialidade_distintivo_id', $especialidadeDistintivoId)
                    ->where('item_novo_id', $itemNovoId)
                    ->exists();

                if ($existe) {
                    $ignoradas++;

                    continue;
                }

                EquivalenciaEspecialidade::create([
                    'especialidade_distintivo_id' => $especialidadeDistintivoId,
                    'item_novo_id' => $itemNovoId,
                    'nivel_minimo' => $nivelMinimo,
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
