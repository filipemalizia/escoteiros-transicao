<?php

namespace App\Filament\Resources\Jovens\Pages;

use App\Concerns\ExibeProgressoDoJovem;
use App\Filament\Resources\Jovens\JovemResource;
use App\Livewire\Portal\Catalogo;
use App\Models\BlocoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\EtapaProgressaoService;
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

    public string $buscaEspecialidades = '';

    public string $buscaInsignias = '';

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
     * @return Collection<int, EspecialidadeDistintivo>
     */
    public function getEspecialidadesFiltradas(): Collection
    {
        return $this->filtrarPorBusca(
            $this->getEspecialidadesDisponiveis()->where('tipo', 'Especialidade'),
            $this->buscaEspecialidades,
        );
    }

    /**
     * @return Collection<int, EspecialidadeDistintivo>
     */
    public function getInsigniasFiltradas(): Collection
    {
        return $this->filtrarPorBusca(
            $this->getEspecialidadesDisponiveis()->where('tipo', 'Insígnia'),
            $this->buscaInsignias,
        );
    }

    /**
     * Quantos itens de Especialidade/Insígnia (conforme `$tipo`) o jovem
     * pediu avaliação e ainda não foram confirmados/rejeitados — usado pro
     * badge da aba correspondente, sempre sobre o total real (ignora a
     * busca, que só afeta o que é exibido na lista).
     */
    public function getAvaliacoesPendentesPorTipo(string $tipo): int
    {
        $itensIds = $this->getEspecialidadesDisponiveis()
            ->where('tipo', $tipo)
            ->flatMap(fn (EspecialidadeDistintivo $especialidade) => $especialidade->grupos->flatMap->itens)
            ->pluck('id');

        return ProgressoEspecialidade::query()
            ->where('jovem_id', $this->getRecord()->id)
            ->whereIn('especialidade_distintivo_item_id', $itensIds)
            ->where('solicitado_pelo_jovem', true)
            ->where('concluido', false)
            ->count();
    }

    /**
     * Filtro por nome usado nas seções de Especialidades/Insígnias da tela
     * do chefe, cada uma com sua própria busca (a seção de Especialidades
     * sozinha já acumula itens demais pra rolar procurando manualmente) —
     * mesmo campo `nome` que o próprio jovem já pode buscar no catálogo do
     * portal ({@see Catalogo::especialidadesFiltradas()}).
     *
     * @param  Collection<int, EspecialidadeDistintivo>  $especialidades
     * @return Collection<int, EspecialidadeDistintivo>
     */
    private function filtrarPorBusca(Collection $especialidades, string $busca): Collection
    {
        return $especialidades
            ->when(
                filled($busca),
                fn (Collection $especialidades) => $especialidades->filter(
                    fn (EspecialidadeDistintivo $especialidade) => Str::contains($especialidade->nome, $busca, ignoreCase: true)
                )
            )
            ->values();
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
        $bloco = ItemNovo::findOrFail($itemNovoId)->bloco;
        [$trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes] = $this->capturarProgressoNovoAntes($bloco);

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

        $this->celebrarProgressoNovo($bloco, $trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes);
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
        $bloco = ItemNovo::findOrFail($itemNovoId)->bloco;
        [$trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes] = $this->capturarProgressoNovoAntes($bloco);

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

        $this->celebrarProgressoNovo($bloco, $trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes);

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
        $especialidade = EspecialidadeDistintivoItem::findOrFail($especialidadeDistintivoItemId)->grupo->especialidadeDistintivo;
        $statusAntes = $this->statusEspecialidade($especialidade);

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

        $this->celebrarSeEspecialidadeConcluiu($especialidade, $statusAntes);
    }

    public function confirmarEspecialidade(int $especialidadeDistintivoItemId): void
    {
        $especialidade = EspecialidadeDistintivoItem::findOrFail($especialidadeDistintivoItemId)->grupo->especialidadeDistintivo;
        $statusAntes = $this->statusEspecialidade($especialidade);

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

        $this->celebrarSeEspecialidadeConcluiu($especialidade, $statusAntes);

        $this->fecharAvaliacao();
    }

    /**
     * Dispara o popup de celebração (mesmo cartão compartilhável do portal
     * do jovem, {@see resources/views/components/progresso/modal-compartilhar.blade.php})
     * quando a ação do chefe (toggle/confirmação) acabou de concluir a
     * Especialidade/Insígnia ou de fazê-la subir de nível — nunca dispara
     * ao desmarcar, nem quando ela já estava concluída/nesse nível antes.
     */
    private function celebrarSeEspecialidadeConcluiu(EspecialidadeDistintivo $especialidade, array $statusAntes): void
    {
        $statusDepois = $this->statusEspecialidade($especialidade);

        $concluiuAgora = $statusAntes['status'] !== 'Concluído' && $statusDepois['status'] === 'Concluído';
        $subiuDeNivel = ($statusDepois['nivel_atingido'] ?? 0) > ($statusAntes['nivel_atingido'] ?? 0);

        if (! $concluiuAgora && ! $subiuDeNivel) {
            return;
        }

        $this->dispararCartaoConquista(
            tipo: $especialidade->tipo === 'Insígnia' ? 'insignia' : 'especialidade',
            titulo: $especialidade->nome,
            imagemUrl: $especialidade->dataUriImagemParaNivel($statusDepois['nivel_atingido'] ?? 1),
            nivel: $statusDepois['nivel_atingido'],
        );
    }

    /**
     * Estado "antes" da trilha de Etapas e do Bloco/Eixo afetados por uma
     * ação de item do Programa Novo (toggle/confirmação de item Novo ou
     * Personalizado) — capturado sempre antes de gravar o progresso, pra
     * {@see celebrarProgressoNovo()} conseguir comparar com o "depois".
     *
     * @return array{0: array<int, array{alcancado: bool}>, 1: bool, 2: bool}
     */
    private function capturarProgressoNovoAntes(BlocoNovo $bloco): array
    {
        return [
            app(EtapaProgressaoService::class)->trilhaEtapaNovo($this->getRecord()),
            $this->statusBloco($bloco)['status'] === 'Concluído',
            $this->eixoConcluido($bloco->eixo),
        ];
    }

    /**
     * Dispara no máximo 1 popup de celebração por ação, na ordem de
     * relevância Etapa/Reconhecimento > Eixo > Bloco — evita empilhar vários
     * popups quando uma única ação (ex.: o último item de um Bloco) fecha
     * o Bloco, o Eixo e uma Etapa ao mesmo tempo.
     *
     * @param  array<int, array{alcancado: bool}>  $trilhaAntes
     */
    private function celebrarProgressoNovo(BlocoNovo $bloco, array $trilhaAntes, bool $blocoJaConcluidoAntes, bool $eixoJaConcluidoAntes): void
    {
        if ($this->celebrarNovasEtapas($trilhaAntes)) {
            return;
        }

        if ($blocoJaConcluidoAntes || $this->statusBloco($bloco)['status'] !== 'Concluído') {
            return;
        }

        $eixo = $bloco->eixo;

        if (! $eixoJaConcluidoAntes && $this->eixoConcluido($eixo)) {
            $this->dispararCartaoConquista(
                tipo: 'eixo',
                titulo: $eixo->nome,
                imagemUrl: $eixo->categoriaImagem?->dataUriImagem(),
            );

            return;
        }

        $this->dispararCartaoConquista(
            tipo: 'bloco',
            titulo: $bloco->titulo,
            imagemUrl: $bloco->categoriaImagem?->dataUriImagem(),
        );
    }

    /**
     * Dispara o popup de celebração quando a trilha de Etapas do Programa
     * Novo ({@see EtapaProgressaoService::trilhaEtapaNovo()}) acabou de
     * alcançar um novo marco (Etapa ou o Reconhecimento máximo). Devolve se
     * disparou, pra {@see celebrarProgressoNovo()} saber que não precisa
     * checar Eixo/Bloco depois (evita empilhar popups na mesma ação).
     *
     * @param  array<int, array{alcancado: bool}>  $trilhaAntes
     */
    private function celebrarNovasEtapas(array $trilhaAntes): bool
    {
        $trilhaDepois = app(EtapaProgressaoService::class)->trilhaEtapaNovo($this->getRecord());

        foreach ($trilhaDepois as $indice => $marco) {
            if ($marco['alcancado'] && ! ($trilhaAntes[$indice]['alcancado'] ?? false)) {
                $this->dispararCartaoConquista(
                    tipo: $marco['tipo'],
                    titulo: $marco['label'],
                    imagemUrl: $marco['imagem_data_uri'],
                );

                return true;
            }
        }

        return false;
    }

    private function dispararCartaoConquista(string $tipo, string $titulo, ?string $imagemUrl, ?int $nivel = null): void
    {
        $this->dispatch(
            'abrir-cartao-conquista',
            tipo: $tipo,
            titulo: $titulo,
            imagemUrl: $imagemUrl,
            jovemNome: $this->getRecord()->nomeExibicao(),
            ramoNome: $this->getRecord()->ramoAtual->nome,
            nivel: $nivel,
        );
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

        $bloco = $item->bloco;
        [$trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes] = $this->capturarProgressoNovoAntes($bloco);

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

        $this->celebrarProgressoNovo($bloco, $trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes);
    }

    public function confirmarItemPersonalizado(int $itemPersonalizadoId): void
    {
        $item = ItemPersonalizado::findOrFail($itemPersonalizadoId);

        abort_unless(auth()->user()?->can('update', $item), 403);

        $bloco = $item->bloco;
        [$trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes] = $this->capturarProgressoNovoAntes($bloco);

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

        $this->celebrarProgressoNovo($bloco, $trilhaAntes, $blocoJaConcluidoAntes, $eixoJaConcluidoAntes);

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
