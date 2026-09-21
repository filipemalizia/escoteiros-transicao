<?php

use App\Http\Controllers\Portal\AutenticacaoController;
use App\Livewire\Portal\Catalogo;
use App\Livewire\Portal\EixoDetalhe;
use App\Livewire\Portal\Inicio;
use App\Livewire\Portal\Revisao;
use App\Livewire\Portal\Timeline;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/entrar', [AutenticacaoController::class, 'mostrarFormulario'])->name('login.mostrar');
    Route::post('/entrar', [AutenticacaoController::class, 'autenticar'])
        ->middleware('throttle:portal-login')
        ->name('login');

    Route::middleware('portal.auth')->group(function () {
        Route::get('/', Inicio::class)->name('progresso');
        Route::get('/linha-do-tempo', Timeline::class)->name('timeline');
        Route::get('/revisao', Revisao::class)->name('revisao');
        Route::get('/eixos/{eixo}', EixoDetalhe::class)->name('eixos.show');
        Route::get('/catalogo/{tipo}', Catalogo::class)->name('catalogo');
    });
});
