<?php

use App\Http\Controllers\Publico\ComiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Publico\RegionController;
use App\Http\Controllers\Publico\ProvinciaController;
use App\Http\Controllers\Publico\DistritoController;
use App\Http\Controllers\Publico\SectorController;
use App\Http\Controllers\Publico\BaseController;
use App\Http\Controllers\Publico\CargoController;
use App\Http\Controllers\Publico\TurnoController;
use App\Http\Controllers\Publico\ConflictoController;
use App\Http\Controllers\Publico\ProgramacionTurnoController;
use App\Http\Controllers\Publico\DenunciaController;
use App\Http\Controllers\Publico\DenunciadoController;
use App\Http\Controllers\Publico\NotificacionController;

Route::group(['middleware' => 'auth:sanctum'], function () {
    //Regiones
    Route::resource('publico/regiones', RegionController::class);
    Route::post('publico/regiones/{id}/activar', [RegionController::class, 'activar']);
    Route::post('publico/regiones/{id}/desactivar', [RegionController::class, 'desactivar']);
    Route::get('publico/regiones/{id}/provincias', [RegionController::class, 'provincias']);
    //Provincias
    Route::resource('publico/provincias', ProvinciaController::class);
    Route::post('publico/provincias/{id}/activar', [ProvinciaController::class, 'activar']);
    Route::post('publico/provincias/{id}/desactivar', [ProvinciaController::class, 'desactivar']);
    Route::get('publico/provincias/{id}/distritos', [ProvinciaController::class, 'getDistritos']);
    //Distritos
    Route::resource('publico/distritos', DistritoController::class);
    Route::post('publico/distritos/{id}/activar', [DistritoController::class, 'activar']);
    Route::post('publico/distritos/{id}/desactivar', [DistritoController::class, 'desactivar']);
    Route::get('publico/distritos/{id}/getSectores', [DistritoController::class, 'getSectores']);
    //Sectores
    Route::resource('publico/sectores', SectorController::class);
    Route::post('publico/sectores/{id}/activar', [SectorController::class, 'activar']);
    Route::post('publico/sectores/{id}/desactivar', [SectorController::class, 'desactivar']);
    Route::get('publico/sectores/{id}/bases', [SectorController::class, 'getBases']);
    //Bases
    Route::resource('publico/bases', BaseController::class);
    Route::post('publico/bases/{id}/activar', [BaseController::class, 'activar']);
    Route::post('publico/bases/{id}/desactivar', [BaseController::class, 'desactivar']);
    Route::get('publico/bases/{id}/getSectores', [BaseController::class, 'getSectores']);
    //Cargos
    Route::resource('publico/cargos', CargoController::class);
    Route::post('publico/cargos/{id}/activar', [CargoController::class, 'activar']);
    Route::post('publico/cargos/{id}/desactivar', [CargoController::class, 'desactivar']);
    //Comites
    Route::resource('publico/comites', ComiteController::class);
    Route::post('publico/comites/getComiteables', [ComiteController::class, 'getComiteables']);
    Route::get('publico/comites/{id_rondero}/cargos', [ComiteController::class, 'getComitesByRondero']);
    //Turnos
    Route::resource('publico/turnos', TurnoController::class);
    Route::post('publico/turnos/{id}/activar', [TurnoController::class, 'activar']);
    Route::post('publico/turnos/{id}/desactivar', [TurnoController::class, 'desactivar']);
    Route::get('publico/turnos/{idTurno}/ListaRonderos', [TurnoController::class, 'ronderosPorTurno']);

    //Programaciòn de Turnos
    Route::resource('publico/programacionTurnos', ProgramacionTurnoController::class);
    Route::post('publico/programacionTurnos/{idTurno}', [ProgramacionTurnoController::class, 'storeDetalleTurno']);

    //Tipos de conflictos
    Route::resource('publico/conflictos', ConflictoController::class);
    Route::post('publico/conflictos/{id}/activar', [ConflictoController::class, 'activar']);
    Route::post('publico/conflictos/{id}/desactivar', [ConflictoController::class, 'desactivar']);

    //Denuncias
    Route::resource('publico/denuncias', DenunciaController::class);
    Route::post('publico/denuncias/{id}/registrarCita', [DenunciaController::class, 'storeCita']);
    Route::post('publico/denuncias/{id}/activar', [DenunciaController::class, 'activar']);
    Route::post('publico/denuncias/{id}/desactivar', [DenunciaController::class, 'desactivar']);
    Route::post('publico/sendMail', [DenunciaController::class, 'sendMail']);
    Route::get('publico/denuncias/{idDenuncia}/ListaNotificaciones', [DenunciaController::class, 'getNotificaciones']);
        //Denunciado
        Route::resource('publico/denunciados', DenunciadoController::class);
        Route::post('publico/denunciados/{id}/activar', [DenunciadoController::class, 'activar']);
        Route::post('publico/denunciados/{id}/desactivar', [DenunciadoController::class, 'desactivar']);

    //Notificaciones
    Route::resource('publico/notificaciones', NotificacionController::class);

});
