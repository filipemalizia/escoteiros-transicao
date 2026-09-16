<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\AutenticarJovemRequest;
use App\Models\Jovem;
use App\Services\Portal\SessaoJovemService;
use Illuminate\Http\RedirectResponse;
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

        $jovem = Jovem::query()
            ->whereNotNull('registro')
            ->whereRaw('LOWER(TRIM(registro)) = ?', [Str::lower(trim($dados['registro']))])
            ->whereDate('data_nascimento', $dados['data_nascimento'])
            ->first();

        if (! $jovem) {
            return back()
                ->withInput($request->only('registro'))
                ->withErrors(['registro' => 'Registro ou data de nascimento incorretos.']);
        }

        $sessao->iniciarSessao($jovem);

        return redirect()->route('portal.progresso');
    }
}
