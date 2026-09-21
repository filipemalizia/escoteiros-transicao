<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Models\EspecialidadeDistintivo;
use App\Models\EspecialidadeDistintivoItem;
use App\Models\ProgressoEspecialidade;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Catalogo extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;

    /**
     * Valor de `tipo` na tabela `especialidades_distintivos` ('Especialidade'
     * ou 'Insígnia') — resolvido a partir do segmento da rota no mount().
     */
    public string $tipo;

    public string $busca = '';

    public ?int $eixoId = null;

    /**
     * 'todas' (catálogo completo, com busca/filtro) ou 'minhas' (só as que
     * já têm algum progresso) — ajuda o jovem a achar rápido o que já
     * começou, sem precisar rolar o catálogo inteiro toda vez.
     */
    public string $aba = 'todas';

    /**
     * Id da especialidade/insígnia aberta no modal/drawer de detalhe agora
     * (null = nenhuma). Fica tudo dentro da URL do catálogo — nunca navega
     * pra uma página própria com o id no endereço.
     */
    public ?int $especialidadeAbertaId = null;

    /**
     * Observação opcional que o jovem pode escrever ao enviar um item pra
     * avaliação, indexada por especialidade_distintivo_item_id.
     *
     * @var array<int, string>
     */
    public array $observacoesAvaliacao = [];

    public ?int $enviandoAvaliacaoItemId = null;

    public function mount(string $tipo, SessaoJovemService $sessao): void
    {
        $this->autenticarJovemNoPortal($sessao);

        $this->tipo = match ($tipo) {
            'especialidades' => 'Especialidade',
            'insignias' => 'Insígnia',
            default => abort(404),
        };
    }

    public function titulo(): string
    {
        return $this->tipo === 'Insígnia' ? 'Insígnias' : 'Especialidades';
    }

    /**
     * @return Collection<int, EspecialidadeDistintivo>
     */
    public function especialidadesFiltradas(): Collection
    {
        $especialidades = EspecialidadeDistintivo::query()
            ->where('tipo', $this->tipo)
            ->paraRamo($this->jovem()->ramo_atual_id)
            ->when(
                $this->tipo === 'Insígnia',
                fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('modalidade')
                    ->orWhereIn('modalidade', ['Geral', $this->jovem()->modalidade()])
                ),
            )
            ->when($this->busca, fn ($query) => $query->where('nome', 'like', '%'.$this->busca.'%'))
            ->when($this->tipo !== 'Insígnia' && $this->eixoId, fn ($query) => $query->whereHas(
                'eixosNovos',
                fn ($query) => $query->where('eixos_novos.id', $this->eixoId),
            ))
            ->with('grupos.itens')
            ->orderBy('nome')
            ->get();

        if ($this->tipo !== 'Insígnia' && $this->aba === 'minhas') {
            $especialidades = $especialidades->filter(
                fn (EspecialidadeDistintivo $especialidade) => $this->statusEspecialidade($especialidade)['status'] !== 'Pendente'
            )->values();
        }

        return $especialidades;
    }

    /**
     * Especialidade/Insígnia aberta no momento no modal/drawer — só se
     * realmente estiver disponível pro ramo atual deste jovem (nunca
     * confiar cegamente num ID vindo do cliente).
     */
    public function getEspecialidadeAberta(): ?EspecialidadeDistintivo
    {
        if (! $this->especialidadeAbertaId) {
            return null;
        }

        return EspecialidadeDistintivo::query()
            ->where('id', $this->especialidadeAbertaId)
            ->paraRamo($this->jovem()->ramo_atual_id)
            ->with('grupos.itens')
            ->first();
    }

    public function abrirEspecialidade(int $especialidadeId): void
    {
        $this->especialidadeAbertaId = $especialidadeId;
    }

    public function fecharEspecialidade(): void
    {
        $this->especialidadeAbertaId = null;
        $this->fecharEnvioAvaliacao();
    }

    public function abrirEnvioAvaliacao(int $itemId): void
    {
        $this->enviandoAvaliacaoItemId = $itemId;
    }

    public function fecharEnvioAvaliacao(): void
    {
        $this->enviandoAvaliacaoItemId = null;
    }

    /**
     * Mesma lógica das outras "solicitar" do portal — só funciona se a
     * especialidade realmente estiver disponível pro ramo atual deste
     * jovem (nunca confiar cegamente num ID vindo do cliente).
     */
    public function solicitarEspecialidade(int $especialidadeDistintivoItemId, SessaoJovemService $sessao): void
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

        $progresso = ProgressoEspecialidade::query()->firstOrNew([
            'jovem_id' => $this->jovemId,
            'especialidade_distintivo_item_id' => $especialidadeDistintivoItemId,
        ]);

        if ($progresso->concluido) {
            return;
        }

        $progresso->solicitado_pelo_jovem = true;
        $progresso->solicitado_em = now();
        $progresso->observacao_jovem = trim($this->observacoesAvaliacao[$especialidadeDistintivoItemId] ?? '') ?: null;
        $progresso->save();

        unset($this->observacoesAvaliacao[$especialidadeDistintivoItemId]);
        $this->fecharEnvioAvaliacao();
    }

    public function getItemParaEnviarAvaliacao(): ?EspecialidadeDistintivoItem
    {
        return $this->enviandoAvaliacaoItemId ? EspecialidadeDistintivoItem::find($this->enviandoAvaliacaoItemId) : null;
    }

    public function render(): View
    {
        return view('livewire.portal.catalogo')
            ->layout('components.layouts.portal-detalhe', [
                'title' => $this->titulo(),
                'voltarPara' => route('portal.progresso'),
            ]);
    }
}
