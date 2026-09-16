<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    /**
     * O nome da ferramenta precisa vir logo depois do logo, antes do nome
     * do portal — como o header padrão do Filament só tem 2 fatias (heading
     * grande + subheading pequeno, nessa ordem), as duas informações viram
     * uma só, dentro do heading: a marca (menor) em cima, "Portal
     * Administrativo" (o heading de verdade) embaixo.
     */
    public function getHeading(): string|Htmlable|null
    {
        return new HtmlString(
            view('filament.components.brand-nome', ['align' => 'center', 'extraClass' => 'mb-3'])->render()
            .'<span class="block text-2xl font-bold text-gray-950 dark:text-white">Portal Administrativo</span>'
        );
    }
}
