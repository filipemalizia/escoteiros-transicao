<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Models\EixoNovo;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EixoDetalhe extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;

    public EixoNovo $eixo;

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
