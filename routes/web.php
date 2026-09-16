<?php

use App\Http\Controllers\Portal\AutenticacaoController;
use App\Livewire\Portal\Progresso;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/entrar', [AutenticacaoController::class, 'mostrarFormulario'])->name('login.mostrar');
    Route::post('/entrar', [AutenticacaoController::class, 'autenticar'])
        ->middleware('throttle:portal-login')
        ->name('login');
    Route::get('/', Progresso::class)->middleware('portal.auth')->name('progresso');
});
