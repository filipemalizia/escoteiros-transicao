<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\AutenticarJovemRequest;
use App\Models\Jovem;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AutenticacaoController extends Controller
{
    public function mostrarFormulario(): View
    {
        return view('portal.login');
    }

    public function autenticar(AutenticarJovemRequest $request, SessaoJovemService $sessao): RedirectResponse
    {
        $dados = $request->validated();
        $registro = Str::lower(trim($dados['registro']));

        // Vale pra qualquer texto digitado, exista o registro ou não, pra
        // não revelar quais registros existem.
        $chaveFalhas = SessaoJovemService::chaveFalhasLogin($registro);

        if (RateLimiter::tooManyAttempts($chaveFalhas, config('portal.login_max_falhas'))) {
            $minutos = (int) ceil(RateLimiter::availableIn($chaveFalhas) / 60);

            return back()
                ->withInput($request->only('registro'))
                ->withErrors(['registro' => "Muitas tentativas erradas para este registro. Tente de novo em {$minutos} minuto(s) ou peça ajuda ao seu chefe."]);
        }

        $jovem = Jovem::query()
            ->whereNotNull('registro')
            ->whereRaw('LOWER(TRIM(registro)) = ?', [$registro])
            ->whereDate('data_nascimento', $dados['data_nascimento'])
            ->first();

        if (! $jovem) {
            RateLimiter::hit($chaveFalhas, config('portal.login_janela_minutos') * 60);

            return back()
                ->withInput($request->only('registro'))
                ->withErrors(['registro' => 'Registro ou data de nascimento incorretos.']);
        }

        RateLimiter::clear($chaveFalhas);

        $sessao->iniciarSessao($jovem);

        return redirect()->route('portal.progresso');
    }
}
