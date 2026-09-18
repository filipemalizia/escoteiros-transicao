<?php

namespace App\Livewire\Portal;

use App\Concerns\ExibeProgressoDoJovem;
use App\Concerns\Portal\AutenticaJovemNoPortal;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Revisao extends Component
{
    use AutenticaJovemNoPortal;
    use ExibeProgressoDoJovem;

    public function mount(SessaoJovemService $sessao): void
    {
        $this->autenticarJovemNoPortal($sessao);
    }

    public function render(): View
    {
        return view('livewire.portal.revisao', [
            'itens' => $this->getItensAguardandoRevisao(),
        ])
            ->layout('components.layouts.portal', ['title' => 'Revisão', 'abaAtiva' => 'revisao']);
    }
}
