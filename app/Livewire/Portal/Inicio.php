<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Inicio extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;

    public bool $modalEtapaAberto = false;

    public bool $modalConquistasAberto = false;

    /**
     * @var array<int, array{data: string, titulo: string, subtitulo: string, imagem_url: ?string, tipo: string}>
     */
    public array $conquistasNovas = [];

    public function mount(SessaoJovemService $sessao): void
    {
        $this->autenticarJovemNoPortal($sessao);

        $this->conquistasNovas = $this->detectarConquistasNovasERegistrarVisita();
        $this->modalConquistasAberto = filled($this->conquistasNovas);

        if ($this->modalConquistasAberto) {
            $this->dispatch('conquista-nova');
        }
    }

    /**
     * `jovens.portal_visitado_em` não guarda literalmente "quando o jovem
     * visitou o portal" — guarda a maior data de conquista (bloco/nível de
     * Especialidade/Insígnia, ver {@see ExibeProgressoDoJovem::getEventosLinhaDoTempo()})
     * que ele já viu. Comparar contra a data da conquista em vez de contra
     * o instante da visita evita depender de hora (`data_conclusao` só tem
     * granularidade de dia) — duas visitas no mesmo dia em que algo novo
     * foi concluído entre elas não perderiam o "algo novo" só porque a
     * primeira visita já tinha um horário "mais tarde" que a meia-noite da
     * data de conclusão.
     *
     * `null` conta como "nunca visto nada ainda" — na primeira visita depois
     * desta coluna existir, qualquer conquista já cadastrada é tratada como
     * nova (um "boas-vindas" único à comemoração, mostrando tudo o que já
     * existe), e a partir daí só mostra de novo o que for mais recente que a
     * última vista.
     *
     * @return array<int, array{data: string, titulo: string, subtitulo: string, imagem_url: ?string, tipo: string}>
     */
    private function detectarConquistasNovasERegistrarVisita(): array
    {
        $jovem = $this->jovem();
        $ultimaConquistaVista = $jovem->portal_visitado_em;
        $eventos = $this->getEventosLinhaDoTempo();
        $maiorDataAtual = collect($eventos)->max('data');

        if ($maiorDataAtual === null) {
            return [];
        }

        $novas = $ultimaConquistaVista === null
            ? $eventos
            : collect($eventos)->filter(fn (array $evento) => $evento['data']->greaterThan($ultimaConquistaVista))->values()->all();

        $jovem->update(['portal_visitado_em' => $maiorDataAtual]);

        // `data` sai como string formatada (não Carbon) porque essa lista
        // vira uma property pública do Livewire — evita depender do synth
        // de Carbon dentro de array aninhado na (de)serialização entre
        // requisições (abrir o modal já resolve tudo no mount(), fechar é
        // só um toggle, não precisa mais do valor original de Carbon).
        return array_map(fn (array $evento) => [
            ...$evento,
            'data' => $evento['data']->format('d/m/Y'),
        ], $novas);
    }

    public function fecharModalConquistas(): void
    {
        $this->modalConquistasAberto = false;
    }

    public function sair(SessaoJovemService $sessao): void
    {
        $sessao->encerrarSessao();

        $this->redirect(route('portal.login.mostrar'));
    }

    public function abrirModalEtapa(): void
    {
        $this->modalEtapaAberto = true;
    }

    public function fecharModalEtapa(): void
    {
        $this->modalEtapaAberto = false;
    }

    public function render(): View
    {
        return view('livewire.portal.inicio')
            ->layout('components.layouts.portal', ['title' => 'Início', 'abaAtiva' => 'inicio']);
    }
}
