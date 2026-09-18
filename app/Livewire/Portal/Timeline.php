<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Timeline extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;

    private const EVENTOS_POR_PAGINA = 30;

    public int $limite = self::EVENTOS_POR_PAGINA;

    public function mount(SessaoJovemService $sessao): void
    {
        $this->autenticarJovemNoPortal($sessao);
    }

    public function carregarMais(): void
    {
        $this->limite += self::EVENTOS_POR_PAGINA;
    }

    public function render(): View
    {
        $eventos = $this->getEventosLinhaDoTempo();

        return view('livewire.portal.timeline', [
            'eventos' => array_slice($eventos, 0, $this->limite),
            'temMais' => count($eventos) > $this->limite,
        ])
            ->layout('components.layouts.portal', ['title' => 'Linha do Tempo', 'abaAtiva' => 'timeline']);
    }
}
