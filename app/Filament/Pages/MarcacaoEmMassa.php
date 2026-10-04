<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ItemNovo;
use App\Models\Jovem;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\Ramo;
use App\Services\StatusProgressaoService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Marcar vários itens (de Progressão e/ou de Especialidade/Insígnia) como
 * concluídos pra vários jovens de uma vez — útil quando um grupo de jovens
 * conclui os mesmos itens junto numa atividade em sede, evitando repetir o
 * mesmo toggle jovem por jovem em {@see VerProgresso}.
 *
 * Mesmo escopo por equipe já usado em
 * {@see VerProgresso::getJovensDisponiveisParaItemPersonalizado()}:
 * disponível pra qualquer chefe, mas só enxerga/marca jovens da própria
 * equipe (admin vê todos). Reaplica o mesmo filtro no backend ao processar
 * o envio, nunca confiando apenas nas opções exibidas no formulário.
 */
class MarcacaoEmMassa extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Ferramentas';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Marcação em Massa';

    protected static ?string $title = 'Marcação em Massa';

    protected string $view = 'filament.pages.marcacao-em-massa';

    public static function canAccess(): bool
    {
        return auth()->check();
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
                Select::make('ramo_id')
                    ->label('Ramo')
                    ->options(fn () => Ramo::query()->orderBy('nome')->pluck('nome', 'id'))
                    ->live()
                    ->required(),

                Select::make('jovens_ids')
                    ->label('Jovens')
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->visible(fn (Get $get) => filled($get('ramo_id')))
                    ->options(fn (Get $get) => static::jovensDisponiveis($get('ramo_id'))->pluck('nome', 'id')),

                Select::make('itens_novo_ids')
                    ->label('Itens de Progressão (Programa Novo)')
                    ->multiple()
                    ->searchable()
                    ->visible(fn (Get $get) => filled($get('ramo_id')))
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesItensNovo($get('ramo_id'), $search))
                    ->getOptionLabelsUsing(fn (array $values) => collect($values)
                        ->mapWithKeys(fn ($value) => [$value => static::labelItemNovo($value)])),

                Select::make('itens_especialidade_ids')
                    ->label('Itens de Especialidade/Insígnia')
                    ->multiple()
                    ->searchable()
                    ->visible(fn (Get $get) => filled($get('ramo_id')))
                    ->getSearchResultsUsing(fn (Get $get, string $search) => static::opcoesItensEspecialidade($get('ramo_id'), $search))
                    ->getOptionLabelsUsing(fn (array $values) => collect($values)
                        ->mapWithKeys(fn ($value) => [$value => static::labelItemEspecialidade($value)])),

                DatePicker::make('data_conclusao')
                    ->label('Data de conclusão')
                    ->default(Carbon::today())
                    ->required(),
            ]);
    }

    /**
     * Jovens do ramo informado que o chefe logado pode gerenciar — mesmo
     * escopo por equipe usado em toda a área administrativa (admin vê
     * todos, chefe comum só vê as próprias equipes).
     *
     * @return Collection<int, Jovem>
     */
    protected static function jovensDisponiveis(?int $ramoId): Collection
    {
        if (blank($ramoId)) {
            return new Collection;
        }

        return Jovem::query()
            ->where('ramo_atual_id', $ramoId)
            ->when(
                ! auth()->user()?->isAdmin(),
                // Jovem sem equipe fica visível pra qualquer chefe — ver
                // {@see \App\Policies\JovemPolicy::podeGerenciar()}.
                fn ($query) => $query->where(
                    fn ($query) => $query
                        ->whereIn('equipe_id', auth()->user()?->equipes()->pluck('equipes.id') ?? [])
                        ->orWhereNull('equipe_id')
                )
            )
            ->orderBy('nome')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    protected static function opcoesItensNovo(?int $ramoId, string $search): array
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

    /**
     * @return array<int, string>
     */
    protected static function opcoesItensEspecialidade(?int $ramoId, string $search): array
    {
        if (blank($ramoId)) {
            return [];
        }

        return EspecialidadeDistintivoItem::query()
            ->whereHas('grupo.especialidadeDistintivo', fn ($query) => $query->paraRamo($ramoId))
            ->where('texto', 'like', "%{$search}%")
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (EspecialidadeDistintivoItem $item) => [$item->id => static::labelItemEspecialidade($item->id, $item)])
            ->all();
    }

    protected static function labelItemNovo(mixed $value, ?ItemNovo $item = null): string
    {
        $item ??= ItemNovo::with('bloco.eixo')->find($value);

        if (! $item) {
            return (string) $value;
        }

        return sprintf('%s / %s / %s: %s', $item->bloco->eixo->nome, $item->bloco->titulo, $item->codigo, Str::limit($item->descricao, 40));
    }

    protected static function labelItemEspecialidade(mixed $value, ?EspecialidadeDistintivoItem $item = null): string
    {
        $item ??= EspecialidadeDistintivoItem::with('grupo.especialidadeDistintivo')->find($value);

        if (! $item) {
            return (string) $value;
        }

        $especialidade = $item->grupo->especialidadeDistintivo;

        return "{$especialidade->tipo}: {$especialidade->nome} / ".Str::limit($item->texto, 40);
    }

    public function marcar(): void
    {
        $data = $this->form->getState();

        $jovensIds = collect($data['jovens_ids'] ?? [])
            ->intersect(static::jovensDisponiveis($data['ramo_id'] ?? null)->pluck('id'))
            ->all();

        $itensNovoIds = $data['itens_novo_ids'] ?? [];
        $itensEspecialidadeIds = $data['itens_especialidade_ids'] ?? [];

        if (empty($jovensIds) || (empty($itensNovoIds) && empty($itensEspecialidadeIds))) {
            Notification::make()
                ->title('Selecione ao menos um jovem e um item.')
                ->danger()
                ->send();

            return;
        }

        $dataConclusao = $data['data_conclusao'] ?? Carbon::today()->toDateString();
        $marcados = 0;
        $ignorados = 0;

        DB::transaction(function () use ($jovensIds, $itensNovoIds, $itensEspecialidadeIds, $dataConclusao, &$marcados, &$ignorados): void {
            foreach ($jovensIds as $jovemId) {
                foreach ($itensNovoIds as $itemNovoId) {
                    $this->marcarConcluido(
                        ProgressoNovo::query()->firstOrNew(['jovem_id' => $jovemId, 'item_novo_id' => $itemNovoId]),
                        $dataConclusao,
                        $marcados,
                        $ignorados,
                    );
                }

                foreach ($itensEspecialidadeIds as $itemEspecialidadeId) {
                    $this->marcarConcluido(
                        ProgressoEspecialidade::query()->firstOrNew(['jovem_id' => $jovemId, 'especialidade_distintivo_item_id' => $itemEspecialidadeId]),
                        $dataConclusao,
                        $marcados,
                        $ignorados,
                    );
                }
            }
        });

        app(StatusProgressaoService::class)->limparCache();

        Notification::make()
            ->title("{$marcados} marcação(ões) feita(s), {$ignorados} já estava(m) concluída(s) e foram ignorada(s).")
            ->success()
            ->send();

        $this->form->fill();
    }

    private function marcarConcluido(ProgressoNovo|ProgressoEspecialidade $progresso, string $dataConclusao, int &$marcados, int &$ignorados): void
    {
        if ($progresso->concluido) {
            $ignorados++;

            return;
        }

        $progresso->concluido = true;
        $progresso->data_conclusao = $dataConclusao;
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->marcado_para_fazer = false;
        $progresso->marcado_para_fazer_em = null;
        $progresso->save();

        $marcados++;
    }
}
