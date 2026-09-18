<?php

namespace App\Providers;

use App\Services\EquivalenciaCreditoService;
use App\Services\StatusProgressaoService;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singletons de propósito: os dois serviços memoizam resultados por
        // (jovem, item/bloco/competência) — sem isso, cada
        // `app(...::class)` (chamado repetidas vezes na tela de progresso)
        // criaria uma instância nova e o cache nunca teria efeito, mantendo
        // o N+1 que causava lentidão no portal do jovem e no painel.
        $this->app->singleton(EquivalenciaCreditoService::class);
        $this->app->singleton(StatusProgressaoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registro + data de nascimento é uma "senha" fraca — limita por IP
        // e também por registro, pra dificultar alguém tentando adivinhar
        // a data de nascimento de um jovem específico usando vários IPs.
        RateLimiter::for('portal-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(10)->by('registro:'.Str::lower(trim((string) $request->input('registro')))),
            ];
        });

        // Padrão brasileiro em toda coluna de tabela que usa ->date()/->dateTime()
        // sem formato explícito (não muda nada no banco, só a exibição).
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultDateDisplayFormat('d/m/Y')
                ->defaultDateTimeDisplayFormat('d/m/Y H:i');
        });
    }
}
