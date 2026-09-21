<?php

namespace App\Concerns;

use App\Models\AreaDesenvolvimentoAntiga;
use App\Models\BlocoNovo;
use App\Models\CompetenciaAntiga;
use App\Models\EixoNovo;
use App\Models\EspecialidadeDistintivo;
use App\Models\EspecialidadeDistintivoGrupo;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ItemAntigo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoAntigo;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\EquivalenciaCreditoService;
use App\Services\EtapaProgressaoService;
use App\Services\ImagemDataUriService;
use App\Services\StatusProgressaoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Getters somente-leitura de progresso de um Jovem, compartilhados entre a
 * tela de progresso do painel (adulto) e o portal público (jovem). Mutações
 * (toggle/confirmação/etc) ficam fora daqui de propósito, pra nunca ficarem
 * acessíveis a partir do portal público.
 */
trait ExibeProgressoDoJovem
{
    /**
     * Ordem de exibição das Áreas de Desenvolvimento do programa antigo
     * (documento oficial, igual pros 4 ramos). Áreas não listadas aqui
     * (ex.: cadastro divergente) aparecem no final, na ordem alfabética
     * padrão, em vez de sumirem.
     */
    protected const ORDEM_AREAS_ANTIGAS = ['Físico', 'Intelectual', 'Caráter', 'Afetivo', 'Social', 'Espiritual'];

    abstract protected function jovem(): Jovem;

    public function getAreasAntigas(): Collection
    {
        return AreaDesenvolvimentoAntiga::query()
            ->where('ramo_id', $this->jovem()->ramo_atual_id)
            ->with('competencias.itens')
            ->orderBy('nome')
            ->get()
            ->sortBy(function (AreaDesenvolvimentoAntiga $area) {
                $indice = array_search($area->nome, self::ORDEM_AREAS_ANTIGAS, true);

                return $indice === false ? 999 : $indice;
            })
            ->values();
    }

    public function getEixosNovos(): Collection
    {
        return EixoNovo::query()
            ->where('ramo_id', $this->jovem()->ramo_atual_id)
            ->with(['blocos.itens.especialidade', 'blocos.equivalenciasBloco.itemAntigo', 'blocos.categoriaImagem'])
            ->orderBy('nome')
            ->get();
    }

    /**
     * Registros diretos de progresso (Fase 4), indexados por item_antigo_id.
     * Usados pra saber se um item foi marcado diretamente (e por quem/quando),
     * em oposição a estar concluído só por crédito de equivalência.
     *
     * @return array<int, ProgressoAntigo>
     */
    public function getProgressoAntigoMap(): array
    {
        return ProgressoAntigo::query()
            ->where('jovem_id', $this->jovem()->id)
            ->with('registradoPor')
            ->get()
            ->keyBy('item_antigo_id')
            ->all();
    }

    /**
     * @return array<int, ProgressoNovo>
     */
    public function getProgressoNovoMap(): array
    {
        return ProgressoNovo::query()
            ->where('jovem_id', $this->jovem()->id)
            ->with('registradoPor')
            ->get()
            ->keyBy('item_novo_id')
            ->all();
    }

    /**
     * @return array{status: string, itens_necessarios: int, itens_concluidos: int}
     */
    public function statusCompetencia(CompetenciaAntiga $competencia): array
    {
        return app(StatusProgressaoService::class)->statusCompetencia($this->jovem(), $competencia);
    }

    /**
     * @return array{status: string, obrigatorias_necessarias: int, obrigatorias_concluidas: int, variaveis_necessarias: int, variaveis_concluidas: int, substitutiva_concluida: bool}
     */
    public function statusBloco(BlocoNovo $bloco): array
    {
        return app(StatusProgressaoService::class)->statusBloco($this->jovem(), $bloco);
    }

    /**
     * Itens do bloco visíveis pra modalidade deste jovem (Básica/Ar/Mar) —
     * usado nas views no lugar de `$bloco->itens` direto, pra nunca mostrar
     * um item que não se aplica a este jovem.
     *
     * @return Collection<int, ItemNovo>
     */
    public function itensVisiveisDoBloco(BlocoNovo $bloco): Collection
    {
        return app(StatusProgressaoService::class)->itensVisiveisDoBloco($this->jovem(), $bloco);
    }

    /**
     * @return array{total: int, concluidas: int, percentual: float}
     */
    public function getPercentualAntigo(): array
    {
        return app(StatusProgressaoService::class)->percentualAntigo($this->jovem());
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function getPercentualNovo(): array
    {
        return app(StatusProgressaoService::class)->percentualNovo($this->jovem());
    }

    /**
     * Percentual "por bloco" fracionário — mais motivador que
     * `getPercentualNovo()` pro portal do jovem, já que conta progresso
     * parcial dentro de um bloco em vez de só 100%/0%. Não substitui
     * `getPercentualNovo()`, que continua sendo a régua oficial.
     *
     * @return array{percentual: float}
     */
    public function getPercentualGamificadoNovo(): array
    {
        return app(StatusProgressaoService::class)->percentualGamificadoNovo($this->jovem());
    }

    /**
     * Null quando o bloco ainda não foi concluído, ou quando nenhum item
     * que fechou o bloco tem `data_conclusao` própria (só creditado por
     * equivalência com o programa antigo).
     */
    public function dataConclusaoBloco(BlocoNovo $bloco): ?Carbon
    {
        return app(StatusProgressaoService::class)->dataConclusaoBloco($this->jovem(), $bloco);
    }

    /**
     * Data em que o jovem atingiu o nível 1 ou 2 de uma Especialidade de
     * estrutura `itens_niveis` — null se ainda não atingiu.
     */
    public function dataNivelEspecialidade(EspecialidadeDistintivo $especialidade, int $nivel): ?Carbon
    {
        return app(StatusProgressaoService::class)->dataNivelEspecialidade($this->jovem(), $especialidade, $nivel);
    }

    /**
     * Data de conclusão de uma Especialidade/Insígnia de estrutura
     * `atividades_temas` — null se ainda não concluída.
     */
    public function dataConclusaoEspecialidade(EspecialidadeDistintivo $especialidade): ?Carbon
    {
        return app(StatusProgressaoService::class)->dataConclusaoEspecialidade($this->jovem(), $especialidade);
    }

    /**
     * Percentual gamificado (0.0-1.0), só dos Blocos de um Eixo — usado pro
     * preenchimento "de onda" do card do Eixo na tela Início.
     */
    public function percentualGamificadoEixo(EixoNovo $eixo): float
    {
        return app(StatusProgressaoService::class)->percentualGamificadoEixo($this->jovem(), $eixo);
    }

    /**
     * Percentual gamificado (0.0-1.0) de um Bloco só — usado na barra de
     * progresso por bloco na tela Início.
     */
    public function percentualGamificadoBloco(BlocoNovo $bloco): float
    {
        return app(StatusProgressaoService::class)->percentualGamificadoBloco($this->jovem(), $bloco);
    }

    /**
     * @return array<int, array{bloco: BlocoNovo, status: string, detalhe: string}>
     */
    public function getPendenciasNovo(): array
    {
        return app(StatusProgressaoService::class)->pendenciasNovo($this->jovem());
    }

    /**
     * @return array<int, ItemAntigo>
     */
    public function getPendenciasAntigo(): array
    {
        return app(StatusProgressaoService::class)->pendenciasAntigo($this->jovem());
    }

    /**
     * Concluído (direto OU crédito de equivalência). Usado pro estado visual do checkbox.
     */
    public function itemAntigoConcluido(ItemAntigo $item): bool
    {
        return app(EquivalenciaCreditoService::class)->itemAntigoConcluido($this->jovem(), $item);
    }

    /**
     * Concluído (direto OU crédito de equivalência). Usado pro estado visual do checkbox.
     */
    public function itemNovoConcluido(ItemNovo $item): bool
    {
        return app(EquivalenciaCreditoService::class)->itemNovoConcluido($this->jovem(), $item);
    }

    public function getEtapaAntigo(): string
    {
        return app(EtapaProgressaoService::class)->etapaAntigo($this->jovem());
    }

    public function getEtapaNovo(): string
    {
        return app(EtapaProgressaoService::class)->etapaNovo($this->jovem());
    }

    /**
     * @return array<int, array{tipo: string, label: string, imagem_url: ?string, alcancado: bool, atual: bool, faltam: int, progresso: float, data_alcancado: ?Carbon}>
     */
    public function getTrilhaEtapaNovo(): array
    {
        return app(EtapaProgressaoService::class)->trilhaEtapaNovo($this->jovem());
    }

    /**
     * Null quando a etapa não tem imagem cadastrada ainda em
     * `public/images/etapas/` — a view trata como "sem imagem" normalmente.
     */
    public function getImagemEtapaNovo(): ?string
    {
        return app(EtapaProgressaoService::class)->imagemEtapa($this->jovem()->ramoAtual->nome, $this->getEtapaNovo());
    }

    /**
     * Null quando o distintivo máximo do ramo não tem imagem cadastrada
     * ainda em `public/images/reconhecimentos/`.
     */
    public function getImagemReconhecimento(): ?string
    {
        return app(EtapaProgressaoService::class)->imagemReconhecimento($this->jovem()->ramoAtual);
    }

    /**
     * Mesma imagem de {@see getImagemReconhecimento()}, mas como data URI
     * base64 em vez de URL — usado só pelo cartão de conquista
     * compartilhável (ver {@see ImagemDataUriService}).
     */
    public function getDataUriImagemReconhecimento(): ?string
    {
        return app(EtapaProgressaoService::class)->dataUriImagemReconhecimento($this->jovem()->ramoAtual);
    }

    public function getElegivelReconhecimentoAntigo(): bool
    {
        return app(EtapaProgressaoService::class)->elegivelReconhecimentoAntigo($this->jovem());
    }

    public function getElegivelReconhecimentoNovo(): bool
    {
        return app(EtapaProgressaoService::class)->elegivelReconhecimentoNovo($this->jovem());
    }

    public function getNomeReconhecimentoAntigo(): string
    {
        return app(EtapaProgressaoService::class)->nomeReconhecimento($this->jovem()->ramoAtual, 'antigo');
    }

    public function getNomeReconhecimentoNovo(): string
    {
        return app(EtapaProgressaoService::class)->nomeReconhecimento($this->jovem()->ramoAtual, 'novo');
    }

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    public function getRequisitosComplementaresAntigo(): array
    {
        return $this->requisitosComValor(
            app(EtapaProgressaoService::class)->chavesComplementaresAntigo($this->jovem()->ramoAtual->nome)
        );
    }

    /**
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    public function getRequisitosComplementaresNovo(): array
    {
        return $this->requisitosComValor(
            app(EtapaProgressaoService::class)->chavesComplementaresNovo($this->jovem()->ramoAtual->nome)
        );
    }

    /**
     * @param  array<int, array{chave: string, tipo: string, label: string, meta?: int}>  $definicoes
     * @return array<int, array{chave: string, tipo: string, label: string, meta?: int, valor: bool|int}>
     */
    protected function requisitosComValor(array $definicoes): array
    {
        return array_map(
            fn (array $definicao) => [
                ...$definicao,
                'valor' => $definicao['tipo'] === 'contador'
                    ? $this->jovem()->requisitoNumero($definicao['chave'])
                    : $this->jovem()->requisitoBool($definicao['chave']),
            ],
            $definicoes
        );
    }

    /**
     * @return array{total: int, concluidos: int, percentual: float}
     */
    public function getResumoAntigo(): array
    {
        return app(StatusProgressaoService::class)->resumoAntigo($this->jovem());
    }

    /**
     * @return array{blocos_total: int, blocos_concluidos: int, obrigatorias_total: int, obrigatorias_concluidas: int, variaveis_minimas_total: int, variaveis_atingidas: int}
     */
    public function getResumoNovo(): array
    {
        return app(StatusProgressaoService::class)->resumoNovo($this->jovem());
    }

    /**
     * Itens "Variável" avulsos criados por um adulto especificamente pra
     * este jovem (ou pra ele e outros), dentro de um bloco — não fazem
     * parte do catálogo oficial (`ItemNovo`).
     *
     * @return Collection<int, ItemPersonalizado>
     */
    public function getItensPersonalizadosDoBloco(BlocoNovo $bloco): Collection
    {
        return ItemPersonalizado::query()
            ->where('bloco_novo_id', $bloco->id)
            ->whereHas('jovens', fn ($query) => $query->where('jovens.id', $this->jovem()->id))
            ->with('criadoPor')
            ->get();
    }

    /**
     * @return array<int, ProgressoPersonalizado>
     */
    public function getProgressoPersonalizadoMap(): array
    {
        return ProgressoPersonalizado::query()
            ->where('jovem_id', $this->jovem()->id)
            ->with('registradoPor')
            ->get()
            ->keyBy('item_personalizado_id')
            ->all();
    }

    public function itemPersonalizadoConcluido(ItemPersonalizado $item): bool
    {
        return ProgressoPersonalizado::query()
            ->where('jovem_id', $this->jovem()->id)
            ->where('item_personalizado_id', $item->id)
            ->where('concluido', true)
            ->exists();
    }

    /**
     * Especialidades/Insígnias disponíveis pro ramo atual do jovem — mesmo
     * filtro que `getEixosNovos()` já faz, só que via `EspecialidadeDistintivo`.
     *
     * @return Collection<int, EspecialidadeDistintivo>
     */
    public function getEspecialidadesDisponiveis(): Collection
    {
        return EspecialidadeDistintivo::query()
            ->paraRamo($this->jovem()->ramo_atual_id)
            ->with('grupos.itens')
            ->orderBy('nome')
            ->get();
    }

    /**
     * @return array<int, ProgressoEspecialidade>
     */
    public function getProgressoEspecialidadeMap(): array
    {
        return ProgressoEspecialidade::query()
            ->where('jovem_id', $this->jovem()->id)
            ->with('registradoPor')
            ->get()
            ->keyBy('especialidade_distintivo_item_id')
            ->all();
    }

    /**
     * @return array{status: string, nivel_atingido: int|null, itens_concluidos: int, itens_totais: int, grupos: array<int, array{grupo: EspecialidadeDistintivoGrupo, concluidos: int, necessarios: int, necessarios_totais: int, satisfeito: bool}>}
     */
    public function statusEspecialidade(EspecialidadeDistintivo $especialidade): array
    {
        return app(StatusProgressaoService::class)->statusEspecialidade($this->jovem(), $especialidade);
    }

    public function itemEspecialidadeConcluido(EspecialidadeDistintivoItem $item): bool
    {
        return ProgressoEspecialidade::query()
            ->where('jovem_id', $this->jovem()->id)
            ->where('especialidade_distintivo_item_id', $item->id)
            ->where('concluido', true)
            ->exists();
    }

    /**
     * Linha do Tempo do portal: junta três fontes já existentes (blocos
     * concluídos, níveis de Especialidade atingidos, Insígnias concluídas)
     * numa lista única ordenada por data decrescente — nenhuma delas tem
     * tabela própria, são todas derivadas via {@see StatusProgressaoService}
     * (Fase 7).
     *
     * @return array<int, array{data: Carbon, titulo: string, subtitulo: string, imagem_url: ?string, imagem_data_uri: ?string, tipo: string}>
     */
    public function getEventosLinhaDoTempo(): array
    {
        $eventos = [
            ...$this->eventosBlocosConcluidos(),
            ...$this->eventosEixosConcluidos(),
            ...$this->eventosEspecialidadesEInsignias(),
            ...$this->eventosEtapasAlcancadas(),
        ];

        usort($eventos, fn (array $a, array $b) => $b['data'] <=> $a['data']);

        return $eventos;
    }

    /**
     * @return array<int, array{data: Carbon, titulo: string, subtitulo: string, imagem_url: ?string, imagem_data_uri: ?string, tipo: string}>
     */
    private function eventosBlocosConcluidos(): array
    {
        $eventos = [];

        foreach ($this->getEixosNovos() as $eixo) {
            foreach ($eixo->blocos as $bloco) {
                if ($this->statusBloco($bloco)['status'] !== 'Concluído') {
                    continue;
                }

                $data = $this->dataConclusaoBloco($bloco);

                if (! $data) {
                    continue;
                }

                $eventos[] = [
                    'data' => $data,
                    'titulo' => $bloco->titulo,
                    'subtitulo' => $eixo->nome,
                    'imagem_url' => $bloco->categoriaImagem?->getFirstMediaUrl('imagem') ?: null,
                    'imagem_data_uri' => $bloco->categoriaImagem?->dataUriImagem(),
                    'tipo' => 'bloco',
                ];
            }
        }

        return $eventos;
    }

    /**
     * Um Eixo conta como concluído quando tem pelo menos 1 Bloco e todos os
     * seus Blocos estão com status "Concluído" — usado tanto pro evento na
     * Linha do Tempo quanto pro botão de compartilhar aparecer no cartão do
     * Eixo (portal e painel do chefe).
     */
    public function eixoConcluido(EixoNovo $eixo): bool
    {
        return $eixo->blocos->isNotEmpty()
            && $eixo->blocos->every(fn (BlocoNovo $bloco) => $this->statusBloco($bloco)['status'] === 'Concluído');
    }

    /**
     * @return array<int, array{data: Carbon, titulo: string, subtitulo: string, imagem_url: ?string, imagem_data_uri: ?string, tipo: string}>
     */
    private function eventosEixosConcluidos(): array
    {
        $eventos = [];

        foreach ($this->getEixosNovos() as $eixo) {
            if (! $this->eixoConcluido($eixo)) {
                continue;
            }

            // Data do Eixo = data do último Bloco que fechou (o que efetivamente concluiu o Eixo).
            $data = $eixo->blocos
                ->map(fn (BlocoNovo $bloco) => $this->dataConclusaoBloco($bloco))
                ->filter()
                ->max();

            if (! $data) {
                continue;
            }

            $eventos[] = [
                'data' => $data,
                'titulo' => $eixo->nome,
                'subtitulo' => 'Eixo concluído',
                'imagem_url' => $eixo->categoriaImagem?->getFirstMediaUrl('imagem') ?: null,
                'imagem_data_uri' => $eixo->categoriaImagem?->dataUriImagem(),
                'tipo' => 'eixo',
            ];
        }

        return $eventos;
    }

    /**
     * @return array<int, array{data: Carbon, titulo: string, subtitulo: string, imagem_url: ?string, imagem_data_uri: ?string, nivel: ?int, tipo: string}>
     */
    private function eventosEspecialidadesEInsignias(): array
    {
        $eventos = [];

        foreach ($this->getEspecialidadesDisponiveis() as $especialidade) {
            $status = $this->statusEspecialidade($especialidade);
            $tipoEvento = $especialidade->tipo === 'Insígnia' ? 'insignia' : 'especialidade';

            if ($status['nivel_atingido'] !== null) {
                foreach ([1, 2] as $nivel) {
                    if ($status['nivel_atingido'] < $nivel) {
                        continue;
                    }

                    $data = $this->dataNivelEspecialidade($especialidade, $nivel);

                    if (! $data) {
                        continue;
                    }

                    $eventos[] = [
                        'data' => $data,
                        'titulo' => $especialidade->nome,
                        'subtitulo' => "Nível {$nivel}",
                        'imagem_url' => $especialidade->urlImagemParaNivel($nivel),
                        'imagem_data_uri' => $especialidade->dataUriImagemParaNivel($nivel),
                        'nivel' => $nivel,
                        'tipo' => $tipoEvento,
                    ];
                }

                continue;
            }

            if ($status['status'] !== 'Concluído') {
                continue;
            }

            $data = $this->dataConclusaoEspecialidade($especialidade);

            if (! $data) {
                continue;
            }

            $eventos[] = [
                'data' => $data,
                'titulo' => $especialidade->nome,
                'subtitulo' => 'Concluída',
                'imagem_url' => $especialidade->urlImagemParaNivel(1),
                'imagem_data_uri' => $especialidade->dataUriImagemParaNivel(1),
                'nivel' => null,
                'tipo' => $tipoEvento,
            ];
        }

        return $eventos;
    }

    /**
     * Distintivos de Etapa (e o Reconhecimento máximo) já alcançados na
     * trilha do Programa Novo ({@see EtapaProgressaoService::trilhaEtapaNovo()})
     * — mesma trilha já usada pro card "Etapas" do portal, só filtrando pros
     * marcos que o jovem já bateu e têm data conhecida.
     *
     * @return array<int, array{data: Carbon, titulo: string, subtitulo: string, imagem_url: ?string, imagem_data_uri: ?string, tipo: string}>
     */
    private function eventosEtapasAlcancadas(): array
    {
        $eventos = [];

        foreach (app(EtapaProgressaoService::class)->trilhaEtapaNovo($this->jovem()) as $marco) {
            if (! $marco['alcancado'] || ! $marco['data_alcancado']) {
                continue;
            }

            $eventos[] = [
                'data' => $marco['data_alcancado'],
                'titulo' => $marco['label'],
                'subtitulo' => $marco['tipo'] === 'reconhecimento' ? 'Reconhecimento' : 'Distintivo de Etapa',
                'imagem_url' => $marco['imagem_url'],
                'imagem_data_uri' => $marco['imagem_data_uri'],
                'tipo' => $marco['tipo'],
            ];
        }

        return $eventos;
    }

    /**
     * Itens que o jovem marcou como feitos e está esperando confirmação de
     * um adulto (Fase 6) — a mesma checagem já usada, espalhada, no painel
     * do chefe (`$aguardandoAvaliacao` em `ver-progresso.blade.php`),
     * consolidada numa lista única pro jovem acompanhar no portal. Sem ação
     * nova aqui: quem aprova/recusa continua sendo o painel do chefe.
     *
     * @return array<int, array{tipo: string, texto: string, contexto: string, observacao: ?string, solicitado_em: Carbon}>
     */
    public function getItensAguardandoRevisao(): array
    {
        $itens = [
            ...$this->itensNovosAguardandoRevisao(),
            ...$this->itensPersonalizadosAguardandoRevisao(),
            ...$this->itensEspecialidadeAguardandoRevisao(),
        ];

        usort($itens, fn (array $a, array $b) => $b['solicitado_em'] <=> $a['solicitado_em']);

        return $itens;
    }

    /**
     * @return array<int, array{tipo: string, texto: string, contexto: string, observacao: ?string, solicitado_em: Carbon}>
     */
    private function itensNovosAguardandoRevisao(): array
    {
        return ProgressoNovo::query()
            ->where('jovem_id', $this->jovem()->id)
            ->where('solicitado_pelo_jovem', true)
            ->where('concluido', false)
            ->with('itemNovo.bloco.eixo')
            ->get()
            ->filter(fn (ProgressoNovo $progresso) => $progresso->itemNovo !== null)
            ->map(fn (ProgressoNovo $progresso) => [
                'tipo' => 'novo',
                'texto' => $progresso->itemNovo->descricao,
                'contexto' => "{$progresso->itemNovo->bloco->titulo} ({$progresso->itemNovo->bloco->eixo->nome})",
                'observacao' => $progresso->observacao_jovem,
                'solicitado_em' => $progresso->solicitado_em,
            ])
            ->all();
    }

    /**
     * @return array<int, array{tipo: string, texto: string, contexto: string, observacao: ?string, solicitado_em: Carbon}>
     */
    private function itensPersonalizadosAguardandoRevisao(): array
    {
        return ProgressoPersonalizado::query()
            ->where('jovem_id', $this->jovem()->id)
            ->where('solicitado_pelo_jovem', true)
            ->where('concluido', false)
            ->with('itemPersonalizado.bloco.eixo')
            ->get()
            ->filter(fn (ProgressoPersonalizado $progresso) => $progresso->itemPersonalizado !== null)
            ->map(fn (ProgressoPersonalizado $progresso) => [
                'tipo' => 'personalizado',
                'texto' => $progresso->itemPersonalizado->descricao,
                'contexto' => "{$progresso->itemPersonalizado->bloco->titulo} ({$progresso->itemPersonalizado->bloco->eixo->nome})",
                'observacao' => $progresso->observacao_jovem,
                'solicitado_em' => $progresso->solicitado_em,
            ])
            ->all();
    }

    /**
     * @return array<int, array{tipo: string, texto: string, contexto: string, observacao: ?string, solicitado_em: Carbon}>
     */
    private function itensEspecialidadeAguardandoRevisao(): array
    {
        return ProgressoEspecialidade::query()
            ->where('jovem_id', $this->jovem()->id)
            ->where('solicitado_pelo_jovem', true)
            ->where('concluido', false)
            ->with('item.grupo.especialidadeDistintivo')
            ->get()
            ->filter(fn (ProgressoEspecialidade $progresso) => $progresso->item !== null)
            ->map(fn (ProgressoEspecialidade $progresso) => [
                'tipo' => 'especialidade',
                'texto' => $progresso->item->texto,
                'contexto' => $progresso->item->grupo->especialidadeDistintivo->nome,
                'observacao' => $progresso->observacao_jovem,
                'solicitado_em' => $progresso->solicitado_em,
            ])
            ->all();
    }
}
