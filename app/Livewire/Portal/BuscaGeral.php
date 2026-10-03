<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Models\ItemNovo;
use App\Models\ProgressoNovo;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Busca única, sem se restringir a um tipo de Especialidade/Insígnia (como
 * o Catálogo) nem a um Eixo (como o EixoDetalhe) — pensada pra "eu lembro
 * mais ou menos o que é, mas não sei se é uma especialidade ou um item do
 * Programa Novo, nem onde procurar".
 */
class BuscaGeral extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;

    #[Url(as: 'q', history: true)]
    public string $busca = '';

    /**
     * Observação opcional do modal de "enviar para avaliação" — mesmo
     * padrão de {@see EixoDetalhe}/{@see QueroFazer}, aqui só pro tipo
     * `ItemNovo` (únicos resultados de progressão que a busca geral traz).
     *
     * @var array<int, string>
     */
    public array $observacoesAvaliacao = [];

    public ?int $enviandoAvaliacaoItemId = null;

    public function mount(SessaoJovemService $sessao): void
    {
        $this->autenticarJovemNoPortal($sessao);
    }

    public function abrirEnvioAvaliacao(int $itemNovoId): void
    {
        $this->enviandoAvaliacaoItemId = $itemNovoId;
    }

    public function fecharEnvioAvaliacao(): void
    {
        $this->enviandoAvaliacaoItemId = null;
    }

    public function getItemParaEnviarAvaliacao(): ?ItemNovo
    {
        return $this->enviandoAvaliacaoItemId ? ItemNovo::find($this->enviandoAvaliacaoItemId) : null;
    }

    /**
     * Mesma lógica de {@see EixoDetalhe::solicitarNovo()}.
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
        $progresso->observacao_jovem = trim($this->observacoesAvaliacao[$itemNovoId] ?? '') ?: null;
        $progresso->save();

        unset($this->observacoesAvaliacao[$itemNovoId]);
        $this->fecharEnvioAvaliacao();
    }

    public function render(): View
    {
        return view('livewire.portal.busca-geral', [
            'especialidades' => $this->especialidadesDaBuscaGeral($this->busca),
            'itensDeProgressao' => $this->itensDeProgressaoDaBuscaGeral($this->busca),
        ])
            ->layout('components.layouts.portal-detalhe', [
                'title' => 'Buscar',
                'voltarPara' => route('portal.progresso'),
            ]);
    }
}
