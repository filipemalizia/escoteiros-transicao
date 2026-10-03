<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Concerns\Portal\MarcaQueroFazer;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ItemNovo;
use App\Models\ItemPersonalizado;
use App\Models\ProgressoEspecialidade;
use App\Models\ProgressoNovo;
use App\Models\ProgressoPersonalizado;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class QueroFazer extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;
    use MarcaQueroFazer;

    /**
     * Observação opcional que o jovem pode escrever ao enviar um item pra
     * avaliação, indexada por tipo ('novo'|'personalizado'|'especialidade')
     * e depois pelo id do item — mesmo esquema de {@see EixoDetalhe}.
     *
     * @var array<string, array<int, string>>
     */
    public array $observacoesAvaliacao = [];

    public ?string $enviandoAvaliacaoTipo = null;

    public ?int $enviandoAvaliacaoItemId = null;

    /**
     * Tipo e item cujo modal de "definir prazo" está aberto no momento —
     * mesmo esquema do modal de avaliação, mas pra `data_alvo` (pra quando
     * o jovem quer fazer aquele item, não quando marcou).
     */
    public ?string $editandoPrazoTipo = null;

    public ?int $editandoPrazoItemId = null;

    public string $editandoPrazoValor = '';

    public function mount(SessaoJovemService $sessao): void
    {
        $this->autenticarJovemNoPortal($sessao);
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
     * Despacha pro método de "enviar" certo, de acordo com o tipo do item
     * cujo modal está aberto — mesmo padrão de
     * {@see EixoDetalhe::enviarAvaliacaoAtual()}.
     */
    public function enviarAvaliacaoAtual(SessaoJovemService $sessao): void
    {
        if (! $this->enviandoAvaliacaoTipo || ! $this->enviandoAvaliacaoItemId) {
            return;
        }

        $this->enviarParaAvaliacao($this->enviandoAvaliacaoTipo, $this->enviandoAvaliacaoItemId, $sessao);

        $this->fecharEnvioAvaliacao();
    }

    public function getItemParaEnviarAvaliacao(): ItemNovo|ItemPersonalizado|EspecialidadeDistintivoItem|null
    {
        if (! $this->enviandoAvaliacaoTipo || ! $this->enviandoAvaliacaoItemId) {
            return null;
        }

        return match ($this->enviandoAvaliacaoTipo) {
            'novo' => ItemNovo::find($this->enviandoAvaliacaoItemId),
            'personalizado' => ItemPersonalizado::find($this->enviandoAvaliacaoItemId),
            'especialidade' => EspecialidadeDistintivoItem::find($this->enviandoAvaliacaoItemId),
            default => null,
        };
    }

    /**
     * Envia um item pra avaliação — mesma checagem de posse que
     * {@see MarcaQueroFazer} já faz pro toggle, mais a observação opcional
     * do modal de envio.
     */
    public function enviarParaAvaliacao(string $tipo, int $itemId, SessaoJovemService $sessao): void
    {
        match ($tipo) {
            'novo' => $this->enviarParaAvaliacaoNovo($itemId, $sessao),
            'personalizado' => $this->enviarParaAvaliacaoPersonalizado($itemId, $sessao),
            'especialidade' => $this->enviarParaAvaliacaoEspecialidade($itemId, $sessao),
            default => null,
        };
    }

    private function enviarParaAvaliacaoNovo(int $itemNovoId, SessaoJovemService $sessao): void
    {
        if (! $sessao->sessaoValida()) {
            $this->redirect(route('portal.login.mostrar'));

            return;
        }

        $progresso = ProgressoNovo::query()->firstOrNew(['jovem_id' => $this->jovemId, 'item_novo_id' => $itemNovoId]);

        if ($progresso->concluido) {
            return;
        }

        $progresso->solicitado_pelo_jovem = true;
        $progresso->solicitado_em = now();
        $progresso->observacao_jovem = trim($this->observacoesAvaliacao['novo'][$itemNovoId] ?? '') ?: null;
        $progresso->save();

        unset($this->observacoesAvaliacao['novo'][$itemNovoId]);
    }

    private function enviarParaAvaliacaoPersonalizado(int $itemPersonalizadoId, SessaoJovemService $sessao): void
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

        $progresso = ProgressoPersonalizado::query()->firstOrNew(['jovem_id' => $this->jovemId, 'item_personalizado_id' => $itemPersonalizadoId]);

        if ($progresso->concluido) {
            return;
        }

        $progresso->solicitado_pelo_jovem = true;
        $progresso->solicitado_em = now();
        $progresso->observacao_jovem = trim($this->observacoesAvaliacao['personalizado'][$itemPersonalizadoId] ?? '') ?: null;
        $progresso->save();

        unset($this->observacoesAvaliacao['personalizado'][$itemPersonalizadoId]);
    }

    private function enviarParaAvaliacaoEspecialidade(int $especialidadeDistintivoItemId, SessaoJovemService $sessao): void
    {
        if (! $sessao->sessaoValida()) {
            $this->redirect(route('portal.login.mostrar'));

            return;
        }

        $disponivelParaORamo = EspecialidadeDistintivoItem::query()
            ->where('id', $especialidadeDistintivoItemId)
            ->whereHas(
                'grupo.especialidadeDistintivo',
                fn ($query) => $query->paraRamo($this->jovem()->ramo_atual_id),
            )
            ->exists();

        abort_unless($disponivelParaORamo, 403);

        $progresso = ProgressoEspecialidade::query()->firstOrNew(['jovem_id' => $this->jovemId, 'especialidade_distintivo_item_id' => $especialidadeDistintivoItemId]);

        if ($progresso->concluido) {
            return;
        }

        $progresso->solicitado_pelo_jovem = true;
        $progresso->solicitado_em = now();
        $progresso->observacao_jovem = trim($this->observacoesAvaliacao['especialidade'][$especialidadeDistintivoItemId] ?? '') ?: null;
        $progresso->save();

        unset($this->observacoesAvaliacao['especialidade'][$especialidadeDistintivoItemId]);
    }

    public function abrirEdicaoPrazo(string $tipo, int $itemId, ?string $prazoAtual): void
    {
        $this->editandoPrazoTipo = $tipo;
        $this->editandoPrazoItemId = $itemId;
        $this->editandoPrazoValor = $prazoAtual ?? '';
    }

    public function fecharEdicaoPrazo(): void
    {
        $this->editandoPrazoTipo = null;
        $this->editandoPrazoItemId = null;
        $this->editandoPrazoValor = '';
    }

    /**
     * @return ProgressoNovo|ProgressoPersonalizado|ProgressoEspecialidade|null
     */
    private function progressoParaEdicaoDePrazo(): mixed
    {
        return match ($this->editandoPrazoTipo) {
            'novo' => ProgressoNovo::where('jovem_id', $this->jovemId)->where('item_novo_id', $this->editandoPrazoItemId)->first(),
            'personalizado' => ProgressoPersonalizado::where('jovem_id', $this->jovemId)->where('item_personalizado_id', $this->editandoPrazoItemId)->first(),
            'especialidade' => ProgressoEspecialidade::where('jovem_id', $this->jovemId)->where('especialidade_distintivo_item_id', $this->editandoPrazoItemId)->first(),
            default => null,
        };
    }

    /**
     * Salva o prazo (pode ficar em branco = sem prazo definido, já que
     * `data_alvo` é opcional).
     */
    public function salvarPrazo(): void
    {
        $progresso = $this->progressoParaEdicaoDePrazo();

        if (! $progresso || ! $progresso->marcado_para_fazer) {
            return;
        }

        $progresso->data_alvo = blank($this->editandoPrazoValor) ? null : $this->editandoPrazoValor;
        $progresso->save();

        $this->fecharEdicaoPrazo();
    }

    public function render(): View
    {
        return view('livewire.portal.quero-fazer', [
            'itens' => $this->getItensMarcadosParaFazer(),
        ])
            ->layout('components.layouts.portal', ['title' => 'Quero Fazer', 'abaAtiva' => 'quero-fazer']);
    }
}
