<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Jovens\Pages\VerProgresso;
use App\Models\EntregaDistintivo;
use App\Models\EntregaEtapa;
use App\Models\EspecialidadeDistintivo;
use App\Models\Jovem;
use App\Services\EspecialidadeStatusService;
use App\Services\EtapaProgressaoService;
use App\Services\StatusProgressaoService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
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
 * Nenhum distintivo é entregue antes da Promessa do jovem: a compra pode ser
 * adiantada, mas "Marcar entregue" fica bloqueado até a data da Promessa ser
 * registrada.
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
     * O jovem do modal de edição ainda não fez a Promessa: a compra pode ser
     * registrada, mas a entrega fica bloqueada.
     */
    public bool $editandoSemPromessa = false;

    /**
     * Uma linha por (jovem, especialidade/insígnia OU etapa/reconhecimento)
     * já conquistada, com o registro de entrega física, se já existir.
     *
     * A parte cara (status de cada especialidade, trilha de etapas com
     * imagens, datas) vem de {@see conquistas()}, calculada uma vez por
     * carregamento da página: cada clique em "Marcar comprado/entregue" é
     * uma requisição Livewire que re-renderiza tudo, e recalcular a
     * conquista de todos os jovens a cada clique era a causa da lentidão.
     * Só as entregas (2 queries leves) são relidas a cada render.
     *
     * @return array<int, array{jovem: array{id: int, nome: string, ramo: string}, origem: string, chave: string, tipo_label: string, titulo: string, nivel_atingido: ?int, primeira_etapa: bool, datas: array<int, array{rotulo: string, data: ?string}>, promessa_realizada: bool, entrega: EntregaDistintivo|EntregaEtapa|null}>
     */
    public function getPendencias(): array
    {
        $conquistas = $this->conquistas();

        $jovemIds = array_values(array_unique(array_column(array_column($conquistas, 'jovem'), 'id')));

        $entregasEspecialidade = EntregaDistintivo::query()
            ->whereIn('jovem_id', $jovemIds)
            ->get()
            ->keyBy(fn (EntregaDistintivo $entrega) => "{$entrega->jovem_id}:{$entrega->especialidade_distintivo_id}");

        $entregasEtapa = EntregaEtapa::query()
            ->whereIn('jovem_id', $jovemIds)
            ->get()
            ->keyBy(fn (EntregaEtapa $entrega) => "{$entrega->jovem_id}:{$entrega->etapa}");

        // A Promessa muda fora desta tela (e as conquistas ficam em cache), então
        // é relida a cada render: 1 query leve, igual às entregas.
        $promessas = Jovem::query()
            ->whereIn('id', $jovemIds)
            ->get(['id', 'ramo_atual_id', 'data_promessa', 'promessa_ramo_id'])
            ->keyBy('id');

        $linhas = [];

        foreach ($conquistas as $conquista) {
            $jovemPromessa = $promessas[$conquista['jovem']['id']] ?? null;
            $promessaRealizada = (bool) $jovemPromessa?->promessaRealizada();

            // A primeira etapa é entregue junto com a Promessa: a data dela é a da Promessa.
            if ($conquista['primeira_etapa'] && $promessaRealizada) {
                $conquista['datas'] = [['rotulo' => 'Alcançada', 'data' => $jovemPromessa->data_promessa->format('d/m/Y')]];
            }

            $entrega = $conquista['origem'] === 'especialidade'
                ? $entregasEspecialidade["{$conquista['jovem']['id']}:{$conquista['chave']}"] ?? null
                : $entregasEtapa["{$conquista['jovem']['id']}:{$conquista['chave']}"] ?? null;

            if (! $this->mostrarEntregues && $entrega?->entregue_em) {
                continue;
            }

            $linhas[] = [...$conquista, 'promessa_realizada' => $promessaRealizada, 'entrega' => $entrega];
        }

        return $linhas;
    }

    /**
     * Tudo o que já foi conquistado pelos jovens visíveis ao usuário, em
     * dados simples (sem models) pra poder ficar em cache entre requisições
     * do mesmo componente. `especialidade->grupos.itens` vem eager-loaded
     * por ramo, e `EspecialidadeStatusService` é singleton com cache por
     * requisição (1 query por jovem).
     *
     * @return array<int, array{jovem: array{id: int, nome: string, ramo: string}, origem: string, chave: string, tipo_label: string, titulo: string, nivel_atingido: ?int, primeira_etapa: bool, datas: array<int, array{rotulo: string, data: ?string}>}>
     */
    #[Computed(persist: true)]
    public function conquistas(): array
    {
        $jovens = Jovem::query()
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

        $statusService = app(EspecialidadeStatusService::class);
        $progressoService = app(StatusProgressaoService::class);
        $etapaService = app(EtapaProgressaoService::class);
        $linhas = [];

        foreach ($jovens as $jovem) {
            $dadosJovem = ['id' => $jovem->id, 'nome' => $jovem->nome, 'ramo' => $jovem->ramoAtual->nome];

            foreach ($especialidadesPorRamo[$jovem->ramo_atual_id] ?? [] as $especialidade) {
                $status = $statusService->statusEspecialidade($jovem, $especialidade);

                if ($status['status'] !== 'Concluído') {
                    continue;
                }

                $linhas[] = [
                    'jovem' => $dadosJovem,
                    'origem' => 'especialidade',
                    'chave' => (string) $especialidade->id,
                    'tipo_label' => $especialidade->tipo,
                    'titulo' => $especialidade->nome,
                    'nivel_atingido' => $status['nivel_atingido'],
                    'primeira_etapa' => false,
                    'datas' => array_map(
                        fn (array $conquista) => ['rotulo' => $conquista['rotulo'], 'data' => $conquista['data']?->format('d/m/Y')],
                        $progressoService->datasConquistaEspecialidade($jovem, $especialidade),
                    ),
                ];
            }

            foreach ($etapaService->trilhaEtapaNovo($jovem) as $indiceMarco => $marco) {
                if (! $marco['alcancado']) {
                    continue;
                }

                $linhas[] = [
                    'jovem' => $dadosJovem,
                    'origem' => 'etapa',
                    'chave' => $marco['label'],
                    'tipo_label' => ucfirst($marco['tipo']),
                    'titulo' => $marco['label'],
                    'nivel_atingido' => null,
                    'primeira_etapa' => $indiceMarco === 0 && $marco['tipo'] === 'etapa',
                    'datas' => [['rotulo' => 'Alcançada', 'data' => $marco['data_alcancado']?->format('d/m/Y')]],
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
        $this->editandoSemPromessa = ! $this->jovemFezPromessa($jovemId);
    }

    public function fecharEdicaoEntrega(): void
    {
        $this->editandoOrigem = null;
        $this->editandoJovemId = null;
        $this->editandoChave = '';
        $this->editandoCompradoEm = '';
        $this->editandoEntregueEm = '';
        $this->editandoSemPromessa = false;
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

        if (filled($this->editandoEntregueEm) && ! $this->jovemFezPromessa($this->editandoJovemId)) {
            $this->avisarPromessaPendente();

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
        if (! $this->jovemFezPromessa($jovemId)) {
            $this->avisarPromessaPendente();

            return;
        }

        $entrega = $this->localizarOuNovaEntrega($origem, $jovemId, $chave);

        $entrega->comprado_em ??= Carbon::today();
        $entrega->entregue_em = Carbon::today();
        $entrega->registrado_por_id = auth()->id();
        $entrega->save();
    }

    private function jovemFezPromessa(int $jovemId): bool
    {
        return (bool) Jovem::find($jovemId)?->promessaRealizada();
    }

    private function avisarPromessaPendente(): void
    {
        Notification::make()
            ->title('Promessa pendente')
            ->body('Nenhum distintivo é entregue antes da Promessa. Registre a data da Promessa do jovem para liberar a entrega.')
            ->warning()
            ->send();
    }

    private function localizarOuNovaEntrega(string $origem, int $jovemId, string $chave): EntregaDistintivo|EntregaEtapa
    {
        return $origem === 'especialidade'
            ? EntregaDistintivo::query()->firstOrNew(['jovem_id' => $jovemId, 'especialidade_distintivo_id' => (int) $chave])
            : EntregaEtapa::query()->firstOrNew(['jovem_id' => $jovemId, 'etapa' => $chave]);
    }
}
