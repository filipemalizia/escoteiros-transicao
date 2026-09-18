<?php

namespace App\Filament\Resources\Jovens\Pages;

use App\Concerns\ExibeProgressoDoJovem;
use App\Filament\Resources\Jovens\JovemResource;
use App\Models\BlocoNovo;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\StatusProgressaoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerProgresso extends Page
{
    use ExibeProgressoDoJovem;
    use InteractsWithRecord;

    protected static string $resource = JovemResource::class;

    protected string $view = 'filament.resources.jovens.pages.ver-progresso';

    public string $abaAtiva = 'novo';

    public ?int $blocoParaNovoItemPersonalizado = null;

    public string $novoItemPersonalizadoDescricao = '';

    /** @var array<int, int> */
    public array $novoItemPersonalizadoOutrosJovensIds = [];

    /**
     * Tipo ('novo'|'personalizado'|'especialidade') e item cujo modal de
     * avaliação (Aprovar/Recusar a solicitação do jovem) está aberto no
     * momento (null = nenhum).
     */
    public ?string $avaliandoTipo = null;

    public ?int $avaliandoItemId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }

    protected function jovem(): Jovem
    {
        return $this->getRecord();
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('baixarPendenciasPdfTodos')
                    ->label('Baixar Todos')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => $this->baixarPendenciasPdf('ambos')),
                Action::make('baixarPendenciasPdfNovo')
                    ->label('Baixar Novo Programa')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => $this->baixarPendenciasPdf('novo')),
                Action::make('baixarPendenciasPdfAntigo')
                    ->label('Baixar Programa Antigo')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => $this->baixarPendenciasPdf('antigo')),
            ])
                ->label('Baixar Pendências (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->button(),
        ];
    }

    protected function baixarPendenciasPdf(string $programa): StreamedResponse
    {
        $jovem = $this->getRecord();
        $service = app(StatusProgressaoService::class);

        $mostrarAntigo = in_array($programa, ['ambos', 'antigo'], true);
        $mostrarNovo = in_array($programa, ['ambos', 'novo'], true);

        $dados = [
            'jovem' => $jovem,
            'mostrarAntigo' => $mostrarAntigo,
            'mostrarNovo' => $mostrarNovo,
            'resumoAntigo' => $mostrarAntigo ? $service->resumoAntigo($jovem) : null,
            'pendenciasAntigo' => $mostrarAntigo ? $service->pendenciasAntigo($jovem) : [],
            'resumoNovo' => $mostrarNovo ? $service->resumoNovo($jovem) : null,
            'pendenciasNovo' => $mostrarNovo ? $service->pendenciasNovo($jovem) : [],
        ];

        $pdf = Pdf::loadView('pdf.pendencias', $dados);

        $sufixo = match ($programa) {
            'novo' => '-programa-novo',
            'antigo' => '-programa-antigo',
            default => '',
        };
        $nomeArquivo = 'pendencias-'.Str::slug($jovem->nome).$sufixo.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $nomeArquivo, ['Content-Type' => 'application/pdf']);
    }

    public function getTitle(): string|Htmlable
    {
        return "Progresso de {$this->getRecord()->nome}";
    }

    public function toggleRequisitoBool(string $chave): void
    {
        $novoValor = ! $this->getRecord()->requisitoBool($chave);

        $this->getRecord()->requisitosComplementares()->updateOrCreate(
            ['chave' => $chave],
            ['tipo' => 'booleano', 'valor_booleano' => $novoValor, 'valor_numero' => null]
        );

        $this->getRecord()->unsetRelation('requisitosComplementares');
    }

    public function atualizarRequisitoNumero(string $chave, int $valor): void
    {
        $this->getRecord()->requisitosComplementares()->updateOrCreate(
            ['chave' => $chave],
            ['tipo' => 'contador', 'valor_numero' => max(0, $valor), 'valor_booleano' => null]
        );

        $this->getRecord()->unsetRelation('requisitosComplementares');
    }

    public function toggleAntigo(int $itemAntigoId): void
    {
        $progresso = ProgressoAntigo::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_antigo_id' => $itemAntigoId,
        ]);

        $concluido = ! $progresso->concluido;

        $progresso->concluido = $concluido;
        $progresso->data_conclusao = $concluido ? Carbon::today() : null;
        $progresso->registrado_por_id = auth()->id();
        // Rede de segurança: se o adulto marcar/desmarcar direto enquanto
        // havia uma solicitação do jovem pendente, ela deixa de fazer sentido.
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();
    }

    public function toggleNovo(int $itemNovoId): void
    {
        $progresso = ProgressoNovo::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_novo_id' => $itemNovoId,
        ]);

        $concluido = ! $progresso->concluido;

        $progresso->concluido = $concluido;
        $progresso->data_conclusao = $concluido ? Carbon::today() : null;
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();
    }

    public function confirmarAntigo(int $itemAntigoId): void
    {
        $progresso = ProgressoAntigo::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_antigo_id' => $itemAntigoId,
        ]);

        $progresso->concluido = true;
        $progresso->data_conclusao = Carbon::today();
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();
    }

    public function confirmarNovo(int $itemNovoId): void
    {
        $progresso = ProgressoNovo::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_novo_id' => $itemNovoId,
        ]);

        $progresso->concluido = true;
        $progresso->data_conclusao = Carbon::today();
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();

        $this->fecharAvaliacao();
    }

    public function rejeitarAntigo(int $itemAntigoId): void
    {
        ProgressoAntigo::query()
            ->where('jovem_id', $this->getRecord()->id)
            ->where('item_antigo_id', $itemAntigoId)
            ->update(['solicitado_pelo_jovem' => false, 'solicitado_em' => null]);
    }

    public function rejeitarNovo(int $itemNovoId): void
    {
        ProgressoNovo::query()
            ->where('jovem_id', $this->getRecord()->id)
            ->where('item_novo_id', $itemNovoId)
            ->update(['solicitado_pelo_jovem' => false, 'solicitado_em' => null]);

        $this->fecharAvaliacao();
    }

    public function toggleEspecialidade(int $especialidadeDistintivoItemId): void
    {
        $progresso = ProgressoEspecialidade::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'especialidade_distintivo_item_id' => $especialidadeDistintivoItemId,
        ]);

        $concluido = ! $progresso->concluido;

        $progresso->concluido = $concluido;
        $progresso->data_conclusao = $concluido ? Carbon::today() : null;
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();
    }

    public function confirmarEspecialidade(int $especialidadeDistintivoItemId): void
    {
        $progresso = ProgressoEspecialidade::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'especialidade_distintivo_item_id' => $especialidadeDistintivoItemId,
        ]);

        $progresso->concluido = true;
        $progresso->data_conclusao = Carbon::today();
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();

        $this->fecharAvaliacao();
    }

    public function rejeitarEspecialidade(int $especialidadeDistintivoItemId): void
    {
        ProgressoEspecialidade::query()
            ->where('jovem_id', $this->getRecord()->id)
            ->where('especialidade_distintivo_item_id', $especialidadeDistintivoItemId)
            ->update(['solicitado_pelo_jovem' => false, 'solicitado_em' => null]);

        $this->fecharAvaliacao();
    }

    public function abrirAvaliacao(string $tipo, int $itemId): void
    {
        $this->avaliandoTipo = $tipo;
        $this->avaliandoItemId = $itemId;
    }

    public function fecharAvaliacao(): void
    {
        $this->avaliandoTipo = null;
        $this->avaliandoItemId = null;
    }

    /**
     * Item (de qualquer um dos 3 tipos) cujo modal de avaliação está aberto
     * no momento — usado pelo modal pra mostrar o texto do item sem a view
     * precisar saber o tipo.
     */
    public function getItemParaAvaliar(): ItemNovo|ItemPersonalizado|EspecialidadeDistintivoItem|null
    {
        if (! $this->avaliandoTipo || ! $this->avaliandoItemId) {
            return null;
        }

        return match ($this->avaliandoTipo) {
            'novo' => ItemNovo::find($this->avaliandoItemId),
            'personalizado' => ItemPersonalizado::find($this->avaliandoItemId),
            'especialidade' => EspecialidadeDistintivoItem::find($this->avaliandoItemId),
            default => null,
        };
    }

    /**
     * Despacha pro método de confirmar/rejeitar certo, de acordo com o
     * tipo do item cujo modal está aberto — usado pelos botões únicos
     * "Aprovar"/"Recusar" do modal, que não sabem (nem precisam saber)
     * qual tipo é.
     */
    public function confirmarAvaliacaoAtual(): void
    {
        match ($this->avaliandoTipo) {
            'novo' => $this->confirmarNovo($this->avaliandoItemId),
            'personalizado' => $this->confirmarItemPersonalizado($this->avaliandoItemId),
            'especialidade' => $this->confirmarEspecialidade($this->avaliandoItemId),
            default => null,
        };
    }

    public function rejeitarAvaliacaoAtual(): void
    {
        match ($this->avaliandoTipo) {
            'novo' => $this->rejeitarNovo($this->avaliandoItemId),
            'personalizado' => $this->rejeitarItemPersonalizado($this->avaliandoItemId),
            'especialidade' => $this->rejeitarEspecialidade($this->avaliandoItemId),
            default => null,
        };
    }

    /**
     * Jovens do mesmo ramo que o adulto logado também pode gerenciar —
     * usado no seletor "também aplicar para" ao criar um item
     * personalizado. Não inclui o próprio jovem desta página (esse já
     * entra automaticamente).
     *
     * @return Collection<int, Jovem>
     */
    public function getJovensDisponiveisParaItemPersonalizado(): Collection
    {
        return Jovem::query()
            ->where('ramo_atual_id', $this->getRecord()->ramo_atual_id)
            ->where('id', '!=', $this->getRecord()->id)
            ->when(
                ! auth()->user()?->isAdmin(),
                fn ($query) => $query->whereIn('equipe_id', auth()->user()?->equipes()->pluck('equipes.id') ?? [])
            )
            ->orderBy('nome')
            ->get();
    }

    public function abrirFormularioItemPersonalizado(int $blocoId): void
    {
        $this->blocoParaNovoItemPersonalizado = $blocoId;
        $this->novoItemPersonalizadoDescricao = '';
        $this->novoItemPersonalizadoOutrosJovensIds = [];
    }

    public function fecharFormularioItemPersonalizado(): void
    {
        $this->blocoParaNovoItemPersonalizado = null;
    }

    public function criarItemPersonalizado(): void
    {
        if (blank($this->novoItemPersonalizadoDescricao) || ! $this->blocoParaNovoItemPersonalizado) {
            return;
        }

        $bloco = BlocoNovo::findOrFail($this->blocoParaNovoItemPersonalizado);

        // Nunca confiar cegamente em IDs vindos do cliente: filtra só pra
        // jovens que o adulto logado realmente pode gerenciar.
        $idsPermitidos = $this->getJovensDisponiveisParaItemPersonalizado()->pluck('id');
        $outrosJovensIds = collect($this->novoItemPersonalizadoOutrosJovensIds)
            ->intersect($idsPermitidos)
            ->all();

        $item = ItemPersonalizado::create([
            'bloco_novo_id' => $bloco->id,
            'descricao' => $this->novoItemPersonalizadoDescricao,
            'criado_por_id' => auth()->id(),
        ]);

        $item->jovens()->attach([$this->getRecord()->id, ...$outrosJovensIds]);

        app(StatusProgressaoService::class)->limparCache();

        $this->fecharFormularioItemPersonalizado();

        Notification::make()
            ->title('Item personalizado criado')
            ->success()
            ->send();
    }

    public function excluirItemPersonalizado(int $itemPersonalizadoId): void
    {
        $item = ItemPersonalizado::findOrFail($itemPersonalizadoId);

        abort_unless(auth()->user()?->can('delete', $item), 403);

        $item->delete();

        app(StatusProgressaoService::class)->limparCache();
    }

    public function toggleItemPersonalizado(int $itemPersonalizadoId): void
    {
        $item = ItemPersonalizado::findOrFail($itemPersonalizadoId);

        abort_unless(auth()->user()?->can('update', $item), 403);

        $progresso = ProgressoPersonalizado::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_personalizado_id' => $itemPersonalizadoId,
        ]);

        $concluido = ! $progresso->concluido;

        $progresso->concluido = $concluido;
        $progresso->data_conclusao = $concluido ? Carbon::today() : null;
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();
    }

    public function confirmarItemPersonalizado(int $itemPersonalizadoId): void
    {
        $item = ItemPersonalizado::findOrFail($itemPersonalizadoId);

        abort_unless(auth()->user()?->can('update', $item), 403);

        $progresso = ProgressoPersonalizado::query()->firstOrNew([
            'jovem_id' => $this->getRecord()->id,
            'item_personalizado_id' => $itemPersonalizadoId,
        ]);

        $progresso->concluido = true;
        $progresso->data_conclusao = Carbon::today();
        $progresso->registrado_por_id = auth()->id();
        $progresso->solicitado_pelo_jovem = false;
        $progresso->solicitado_em = null;
        $progresso->save();

        app(StatusProgressaoService::class)->limparCache();

        $this->fecharAvaliacao();
    }

    public function rejeitarItemPersonalizado(int $itemPersonalizadoId): void
    {
        $item = ItemPersonalizado::findOrFail($itemPersonalizadoId);

        abort_unless(auth()->user()?->can('update', $item), 403);

        ProgressoPersonalizado::query()
            ->where('jovem_id', $this->getRecord()->id)
            ->where('item_personalizado_id', $itemPersonalizadoId)
            ->update(['solicitado_pelo_jovem' => false, 'solicitado_em' => null]);

        $this->fecharAvaliacao();
    }
}
