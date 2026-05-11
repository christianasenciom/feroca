<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DescargasController;
use App\Http\Controllers\Web\VerificacionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {
    return redirect('/signin');
})->name('login');

Route::get('/', function () {
    return view('welcome');
})->name("home");

Route::get('/descargas', [DescargasController::class, "index"])->name("descargas");

// ============================================
// RUTAS API PARA VERIFICACIÓN DE IDENTIDAD
// ============================================
Route::prefix('api/web')->group(function () {
    Route::get('/verificar/dni/{dni}', [VerificacionController::class, 'verificarPorDNI']);
    Route::post('/verificar/datos', [VerificacionController::class, 'verificarDatos']);
});

// ============================================
// RUTA CATCH-ALL - COMENTADA PORQUE CAUSA ERROR
// ============================================
// Route::get('/{any}', function () {
//     return view('index');
// })->where('any', '.*');