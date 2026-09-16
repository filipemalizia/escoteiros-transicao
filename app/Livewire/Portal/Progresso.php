<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Models\ItemPersonalizado;
use App\Models\Jovem;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Progresso extends Component
{
    use ExibeProgressoDoJovem;

    public int $jovemId;

    /**
     * Cache local (não sincronizado pelo Livewire, é propriedade privada) —
     * `jovem()` é chamado dezenas de vezes pelos getters de
     * `ExibeProgressoDoJovem` numa única renderização; sem isso, cada
     * chamada buscava o Jovem de novo no banco.
     */
    private ?Jovem $jovemCache = null;

    public function mount(SessaoJovemService $sessao): void
    {
        $jovem = $sessao->jovemAutenticado();

        abort_if($jovem === null, 403);

        $this->jovemId = $jovem->id;
    }

    protected function jovem(): Jovem
    {
        return $this->jovemCache ??= Jovem::findOrFail($this->jovemId);
    }

    public function sair(SessaoJovemService $sessao): void
    {
        $sessao->encerrarSessao();

        $this->redirect(route('portal.login.mostrar'));
    }

    /**
     * Jovem "avisa" que fez um item — não marca como concluído direto,
     * fica pendente de confirmação de um adulto (ver VerProgresso::confirmarNovo).
     * Só existe pro Programa Novo — o Antigo não fica visível no portal.
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
        $progresso->save();
    }

    /**
     * Mesma lógica de solicitarNovo(), pra um item personalizado — só
     * funciona se o item realmente estiver vinculado a este jovem (nunca
     * confiar cegamente num ID vindo do cliente).
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
        $progresso->save();
    }

    public function render(): View
    {
        return view('livewire.portal.progresso')
            ->layout('components.layouts.portal', ['title' => 'Meu Progresso']);
    }
}
