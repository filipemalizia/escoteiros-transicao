<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Concerns\Portal\MarcaQueroFazer;
use App\Models\BlocoNovo;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\Portal\SessaoJovemService;
use App\Support\Busca;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class EixoDetalhe extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;
    use MarcaQueroFazer;

    public EixoNovo $eixo;

    #[Url(as: 'q', history: true)]
    public string $busca = '';

    /**
     * Id do Bloco que deve vir com o acordeão já aberto — vem de `?bloco=`
     * na URL (ex.: link vindo da Busca Geral), não precisa de `#[Url]`
     * porque só é lido uma vez no mount(), nunca escrito de volta.
     */
    public ?int $blocoAbertoPorPadrao = null;

    /**
     * Observação opcional que o jovem pode escrever ao enviar um item pra
     * avaliação, indexada por tipo ('novo'|'personalizado') e depois pelo id
     * do item. Só existe em memória até o envio.
     *
     * @var array<string, array<int, string>>
     */
    public array $observacoesAvaliacao = [];

    public ?string $enviandoAvaliacaoTipo = null;

    public ?int $enviandoAvaliacaoItemId = null;

    public function mount(EixoNovo $eixo, SessaoJovemService $sessao): void
    {
        $this->autenticarJovemNoPortal($sessao);

        abort_unless($eixo->ramo_id === $this->jovem()->ramo_atual_id, 403);

        $this->eixo = $eixo->load(['blocos.itens.especialidade', 'blocos.equivalenciasBloco.itemAntigo']);
        $this->blocoAbertoPorPadrao = request()->integer('bloco') ?: null;
    }

    /**
     * @param  Collection<int, ItemNovo>  $itens
     * @return Collection<int, ItemNovo>
     */
    public function itensVisiveisNaBusca(Collection $itens): Collection
    {
        if (blank($this->busca)) {
            return $itens;
        }

        return $itens
            ->filter(fn (ItemNovo $item) => Busca::contemTodasAsPalavras([$item->codigo, $item->descricao], $this->busca))
            ->values();
    }

    /**
     * @return Collection<int, ItemPersonalizado>
     */
    public function getItensPersonalizadosDoBlocoFiltrados(BlocoNovo $bloco): Collection
    {
        $itens = $this->getItensPersonalizadosDoBloco($bloco);

        if (blank($this->busca)) {
            return $itens;
        }

        return $itens
            ->filter(fn (ItemPersonalizado $item) => Busca::contemTodasAsPalavras($item->descricao, $this->busca))
            ->values();
    }

    /**
     * Se este Bloco tem algum item (novo ou personalizado) que casa com a
     * busca — usado pra decidir se o acordeão do bloco aparece quando há
     * busca ativa (blocos sem nenhum item correspondente somem da lista).
     */
    public function blocoTemCorrespondenciaNaBusca(BlocoNovo $bloco): bool
    {
        if (blank($this->busca)) {
            return true;
        }

        return $this->itensVisiveisNaBusca($this->itensVisiveisDoBloco($bloco))->isNotEmpty()
            || $this->getItensPersonalizadosDoBlocoFiltrados($bloco)->isNotEmpty();
    }

    /**
     * Jovem "avisa" que fez um item — não marca como concluído direto,
     * fica pendente de confirmação de um adulto.
     */
    public function solicitarNovo(int $itemNovoId, SessaoJovemService $sessao): void
    {
        if (! $sessao->sessaoValida()) {
            $this->redirect(route('portal.login.mostrar'));

            return;
        }

        $progresso = ProgressoNovo::query()->firstOrNew([
            'jovem_id' => $this->jovemId,
            'item_novo_id' => $itemNovoId,
        ]);

        if ($progresso->concluido) {
            return;
        }

        $progresso->solicitado_pelo_jovem = true;
        $progresso->solicitado_em = now();
        $progresso->observacao_jovem = trim($this->observacoesAvaliacao['novo'][$itemNovoId] ?? '') ?: null;
        $progresso->save();

        unset($this->observacoesAvaliacao['novo'][$itemNovoId]);
        $this->fecharEnvioAvaliacao();
    }

    /**
     * Mesma lógica de solicitarNovo(), pra um item personalizado — só
     * funciona se o item realmente estiver vinculado a este jovem.
     */
    public function solicitarItemPersonalizado(int $itemPersonalizadoId, SessaoJovemService $sessao): void
    {
        if (! $sessao->sessaoValida()) {
            $this->redirect(route('portal.login.mostrar'));

            return;
        }

        $pertenceAoJovem = ItemPersonalizado::query()
            ->where('id', $itemPersonalizadoId)
            ->whereHas('jovens', fn ($query) => $query->where('jovens.id', $this->jovemId))
            ->exists();

        abort_unless($pertenceAoJovem, 403);

        $progresso = ProgressoPersonalizado::query()->firstOrNew([
            'jovem_id' => $this->jovemId,
            'item_personalizado_id' => $itemPersonalizadoId,
        ]);

        if ($progresso->concluido) {
            return;
        }

        $progresso->solicitado_pelo_jovem = true;
        $progresso->solicitado_em = now();
        $progresso->observacao_jovem = trim($this->observacoesAvaliacao['personalizado'][$itemPersonalizadoId] ?? '') ?: null;
        $progresso->save();

        unset($this->observacoesAvaliacao['personalizado'][$itemPersonalizadoId]);
        $this->fecharEnvioAvaliacao();
    }

    public function abrirEnvioAvaliacao(string $tipo, int $itemId): void
    {
        $this->enviandoAvaliacaoTipo = $tipo;
        $this->enviandoAvaliacaoItemId = $itemId;
    }

    public function fecharEnvioAvaliacao(): void
    {
        $this->enviandoAvaliacaoTipo = null;
        $this->enviandoAvaliacaoItemId = null;
    }

    /**
     * Despacha pro método de "solicitar" certo, de acordo com o tipo do
     * item cujo modal está aberto.
     */
    public function enviarAvaliacaoAtual(SessaoJovemService $sessao): void
    {
        match ($this->enviandoAvaliacaoTipo) {
            'novo' => $this->solicitarNovo($this->enviandoAvaliacaoItemId, $sessao),
            'personalizado' => $this->solicitarItemPersonalizado($this->enviandoAvaliacaoItemId, $sessao),
            default => null,
        };
    }

    public function getItemParaEnviarAvaliacao(): ItemNovo|ItemPersonalizado|null
    {
        if (! $this->enviandoAvaliacaoTipo || ! $this->enviandoAvaliacaoItemId) {
            return null;
        }

        return match ($this->enviandoAvaliacaoTipo) {
            'novo' => ItemNovo::find($this->enviandoAvaliacaoItemId),
            'personalizado' => ItemPersonalizado::find($this->enviandoAvaliacaoItemId),
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.portal.eixo-detalhe')
            ->layout('components.layouts.portal-detalhe', [
                'title' => $this->eixo->nome,
                'voltarPara' => route('portal.progresso'),
                'logoUrl' => $this->eixo->categoriaImagem?->getFirstMediaUrl('imagem') ?: null,
            ]);
    }
}
