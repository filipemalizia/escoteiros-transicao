<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\EntregaDistintivo;
use App\Models\EntregaEtapa;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Services\EspecialidadeStatusService;
use App\Services\EtapaProgressaoService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * Controle de entrega FÍSICA de distintivos já conquistados (compra na loja
 * nacional + entrega ao jovem) — desacoplado do progresso digital. Cobre
 * Especialidade/Insígnia (por requisito individual, `ProgressoEspecialidade`)
 * e as Etapas/Reconhecimento do Programa Novo (calculadas, sem tabela
 * própria — ver {@see EtapaProgressaoService::trilhaEtapaNovo()}). Etapa do
 * Programa Antigo fica de fora por enquanto (decisão do usuário: normalmente
 * já foi entregue antes desta ferramenta existir, e o serviço não expõe uma
 * trilha completa pra ele, só a etapa atual). Só pro chefe/admin; o jovem
 * nunca vê nada disso, em lugar nenhum.
 *
 * Ao contrário das outras páginas de "Ferramentas" (todas admin-only), esta
 * também é útil pro chefe comum — cada um cuida da entrega física da sua
 * própria equipe — por isso o acesso segue o mesmo escopo por equipe já
 * usado em {@see VerProgresso::getJovensDisponiveisParaItemPersonalizado()}.
 */
class EntregaDistintivos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGiftTop;

    protected static string|UnitEnum|null $navigationGroup = 'Ferramentas';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Entrega de Distintivos';

    protected static ?string $title = 'Entrega de Distintivos';

    protected string $view = 'filament.pages.entrega-distintivos';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public bool $mostrarEntregues = false;

    /**
     * Tipo ('especialidade'|'etapa') e "chave" do item cujo modal de edição
     * de compra/entrega está aberto — a chave é o id da especialidade (como
     * string) ou o nome da etapa, dependendo do tipo.
     */
    public ?string $editandoOrigem = null;

    public ?int $editandoJovemId = null;

    public string $editandoChave = '';

    public string $editandoCompradoEm = '';

    public string $editandoEntregueEm = '';

    /**
     * Uma linha por (jovem, especialidade/insígnia OU etapa/reconhecimento)
     * já conquistada, com o registro de entrega física, se já existir.
     * `especialidade->grupos.itens` já vem eager-loaded por ramo (uma query
     * só por ramo distinto, não por jovem), e `EspecialidadeStatusService`
     * é singleton com cache por requisição (1 query por jovem) — ver notas
     * de performance no service.
     *
     * @return array<int, array{jovem: Jovem, origem: string, chave: string, tipo_label: string, titulo: string, nivel_atingido: ?int, entrega: EntregaDistintivo|EntregaEtapa|null}>
     */
    public function getPendencias(): array
    {
        $jovens = Jovem::query()
            ->when(
                ! auth()->user()?->isAdmin(),
                fn ($query) => $query->whereIn('equipe_id', auth()->user()?->equipes()->pluck('equipes.id') ?? [])
            )
            ->with('ramoAtual')
            ->orderBy('nome')
            ->get();

        $especialidadesPorRamo = $jovens->pluck('ramo_atual_id')->unique()->mapWithKeys(
            fn ($ramoId) => [$ramoId => EspecialidadeDistintivo::query()
                ->paraRamo($ramoId)
                ->with('grupos.itens')
                ->orderBy('tipo')
                ->orderBy('nome')
                ->get()]
        );

        $entregasEspecialidade = EntregaDistintivo::query()
            ->whereIn('jovem_id', $jovens->pluck('id'))
            ->get()
            ->keyBy(fn (EntregaDistintivo $entrega) => "{$entrega->jovem_id}:{$entrega->especialidade_distintivo_id}");

        $entregasEtapa = EntregaEtapa::query()
            ->whereIn('jovem_id', $jovens->pluck('id'))
            ->get()
            ->keyBy(fn (EntregaEtapa $entrega) => "{$entrega->jovem_id}:{$entrega->etapa}");

        $statusService = app(EspecialidadeStatusService::class);
        $etapaService = app(EtapaProgressaoService::class);
        $linhas = [];

        foreach ($jovens as $jovem) {
            foreach ($especialidadesPorRamo[$jovem->ramo_atual_id] ?? [] as $especialidade) {
                $status = $statusService->statusEspecialidade($jovem, $especialidade);

                if ($status['status'] !== 'Concluído') {
                    continue;
                }

                $entrega = $entregasEspecialidade["{$jovem->id}:{$especialidade->id}"] ?? null;

                if (! $this->mostrarEntregues && $entrega?->entregue_em) {
                    continue;
                }

                $linhas[] = [
                    'jovem' => $jovem,
                    'origem' => 'especialidade',
                    'chave' => (string) $especialidade->id,
                    'tipo_label' => $especialidade->tipo,
                    'titulo' => $especialidade->nome,
                    'nivel_atingido' => $status['nivel_atingido'],
                    'entrega' => $entrega,
                ];
            }

            foreach ($etapaService->trilhaEtapaNovo($jovem) as $marco) {
                if (! $marco['alcancado']) {
                    continue;
                }

                $entrega = $entregasEtapa["{$jovem->id}:{$marco['label']}"] ?? null;

                if (! $this->mostrarEntregues && $entrega?->entregue_em) {
                    continue;
                }

                $linhas[] = [
                    'jovem' => $jovem,
                    'origem' => 'etapa',
                    'chave' => $marco['label'],
                    'tipo_label' => ucfirst($marco['tipo']),
                    'titulo' => $marco['label'],
                    'nivel_atingido' => null,
                    'entrega' => $entrega,
                ];
            }
        }

        return $linhas;
    }

    public function abrirEdicaoEntrega(string $origem, int $jovemId, string $chave, ?string $compradoEm, ?string $entregueEm): void
    {
        $this->editandoOrigem = $origem;
        $this->editandoJovemId = $jovemId;
        $this->editandoChave = $chave;
        $this->editandoCompradoEm = $compradoEm ?? '';
        $this->editandoEntregueEm = $entregueEm ?? '';
    }

    public function fecharEdicaoEntrega(): void
    {
        $this->editandoOrigem = null;
        $this->editandoJovemId = null;
        $this->editandoChave = '';
        $this->editandoCompradoEm = '';
        $this->editandoEntregueEm = '';
    }

    /**
     * Marcar entrega sem ter marcado compra antes não faz sentido (não dá
     * pra entregar o que não foi comprado) — se só a entrega foi
     * preenchida, assume a mesma data também pra compra.
     */
    public function salvarEntrega(): void
    {
        if (! $this->editandoOrigem || ! $this->editandoJovemId) {
            return;
        }

        $entrega = $this->localizarOuNovaEntrega($this->editandoOrigem, $this->editandoJovemId, $this->editandoChave);

        $entrega->comprado_em = blank($this->editandoCompradoEm) ? null : $this->editandoCompradoEm;
        $entrega->entregue_em = blank($this->editandoEntregueEm) ? null : $this->editandoEntregueEm;

        if ($entrega->entregue_em && ! $entrega->comprado_em) {
            $entrega->comprado_em = $entrega->entregue_em;
        }

        $entrega->registrado_por_id = auth()->id();
        $entrega->save();

        $this->fecharEdicaoEntrega();
    }

    /**
     * Atalho pra marcar "comprado hoje" direto da lista, sem abrir o modal.
     */
    public function marcarCompradoHoje(string $origem, int $jovemId, string $chave): void
    {
        $entrega = $this->localizarOuNovaEntrega($origem, $jovemId, $chave);

        $entrega->comprado_em ??= Carbon::today();
        $entrega->registrado_por_id = auth()->id();
        $entrega->save();
    }

    /**
     * Atalho pra marcar "entregue hoje" direto da lista — também garante a
     * compra (mesma regra de {@see salvarEntrega()}).
     */
    public function marcarEntregueHoje(string $origem, int $jovemId, string $chave): void
    {
        $entrega = $this->localizarOuNovaEntrega($origem, $jovemId, $chave);

        $entrega->comprado_em ??= Carbon::today();
        $entrega->entregue_em = Carbon::today();
        $entrega->registrado_por_id = auth()->id();
        $entrega->save();
    }

    private function localizarOuNovaEntrega(string $origem, int $jovemId, string $chave): EntregaDistintivo|EntregaEtapa
    {
        return $origem === 'especialidade'
            ? EntregaDistintivo::query()->firstOrNew(['jovem_id' => $jovemId, 'especialidade_distintivo_id' => (int) $chave])
            : EntregaEtapa::query()->firstOrNew(['jovem_id' => $jovemId, 'etapa' => $chave]);
    }
}
