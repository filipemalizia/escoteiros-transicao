<?php

namespace App\Http\Middleware;

use App\Services\Portal\SessaoJovemService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalJovemAutenticado
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(SessaoJovemService::class)->sessaoValida()) {
            return redirect()
                ->route('portal.login.mostrar')
                ->withErrors(['registro' => 'Sua sessão expirou. Entre novamente.']);
        }

        return $next($request);
    }
}
