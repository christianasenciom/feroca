<?php

use App\Http\Controllers\Publico\ComiteController;
use App\Http\Controllers\Publico\ReportesController;
use App\Http\Controllers\Publico\TurnoController;
use App\Http\Controllers\UtilsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('publico/comites/cargos_validos', [ComiteController::class, 'getAvailableCargos']);
    Route::post('consultar-rq', [UtilsController::class, 'consultarRQ']);
    Route::put('asamblea/updateAsistencia/{id}', [TurnoController::class, 'updateAsistencia']);
    Route::post('/asamblea/subirFotoDocumento/{id}', [TurnoController::class, 'subirImagenAsamblea']);

    Route::post('publico/reportes/denuncias-fechas', [ReportesController::class, 'denunciasPorFechas']);
    Route::post('publico/reportes/denuncias-fechas-excel', [ReportesController::class, 'denunciasPorFechasExcel']);
    Route::post('publico/reportes/denuncias-persona', [ReportesController::class, 'denunciasPorPersona']);
    Route::post('publico/reportes/denuncias-persona-excel', [ReportesController::class, 'denunciasPorPersonaExcel']);
    Route::post('publico/reportes/tipos', [ReportesController::class, 'obtenerDatosPorTipo']);
    Route::post('publico/reportes/denuncias-criterios', [ReportesController::class, 'denunciasPorCriterios']);
    Route::post('publico/reportes/denuncias-criterios-excel', [ReportesController::class, 'denunciasPorCriteriosExcel']);
    Route::post('publico/reportes/asambleas-fechas', [ReportesController::class, 'asambleasPorFechas']);
    Route::post('publico/reportes/asambleas-fechas-excel', [ReportesController::class, 'asambleasPorFechasExcel']);
});
