<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\QRController;
use App\Http\Controllers\Admin\ConfiguracionController;

// Ruta CSRF (pública)
Route::get('/sanctum/csrf-cookie', function () {
    return response()->noContent();
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('auth/signin', [WebAuthController::class, 'SignIn']);
Route::get('validar-qr/{qr}', [QRController::class, 'validarQr']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {

    // ==========================================
    // AUTH
    // ==========================================
    Route::get('auth/webuser', [WebAuthController::class, 'UserInfo']);
    Route::post('auth/weblogout', [WebAuthController::class, 'SingOutCurrentSession']);
    Route::post('auth/weblogoutall', [WebAuthController::class, 'SingOutAllSessions']);
    Route::post('auth/newpassword', [WebAuthController::class, 'passwordResset']);
    Route::post('auth/updateimage', [WebAuthController::class, 'updateImage']);
    Route::post('auth/initnewpassword', [WebAuthController::class, 'passwordRessetInit']);

    // ==========================================
    // USUARIOS Y PERMISOS
    // ==========================================
    Route::resource('auth/permisos', App\Http\Controllers\Auth\PermissionController::class);
    Route::get('auth/permisosall', [App\Http\Controllers\Auth\PermissionController::class, 'all']);
    Route::get('permissionsforrole', [App\Http\Controllers\Auth\PermissionController::class, 'fetchPermissionsForRole']);
    Route::resource('auth/roles', App\Http\Controllers\Auth\RoleController::class);
    Route::get('auth/rolesall', [App\Http\Controllers\Auth\RoleController::class, 'all']);
    Route::post('syncRolePermissions', [App\Http\Controllers\Auth\RoleController::class, 'syncRolePermissions']);
    Route::resource('auth/usuarios', App\Http\Controllers\Auth\UserController::class);

    // ==========================================
    // PERSONAS
    // ==========================================
    Route::resource('publico/personas', App\Http\Controllers\Publico\PersonaController::class);

    // ==========================================
    // RONDEROS
    // ==========================================
    Route::resource('publico/ronderos', App\Http\Controllers\Publico\RonderoController::class);
    Route::post('publico/ronderos/{id}/activar', [App\Http\Controllers\Publico\RonderoController::class, 'activar']);
    Route::post('publico/ronderos/{id}/desactivar', [App\Http\Controllers\Publico\RonderoController::class, 'inactivar']);
    Route::post('generar-carnet', [App\Http\Controllers\Publico\RonderoController::class, 'generarCarnet']);
    Route::get('publico/ronderos/{id}/foto', [App\Http\Controllers\Publico\RonderoController::class, 'getFoto']);

    // Ronderos potenciales administradores
    Route::get('publico/ronderos/potenciales-administradores', [App\Http\Controllers\Publico\RonderoController::class, 'potencialesAdministradores']);

    // ==========================================
    // BASES
    // ==========================================
    Route::resource('publico/bases', App\Http\Controllers\Publico\BaseController::class);
    Route::post('publico/bases/eliminar-masivo', [App\Http\Controllers\Publico\BaseController::class, 'eliminarMasivo']);
    Route::get('publico/bases/ronderos', [App\Http\Controllers\Publico\BaseController::class, 'getRonderosByBase']);
    Route::get('publico/distritos/{id}/getSectores', [App\Http\Controllers\Publico\DistritoController::class, 'getSectores']);
    Route::get('publico/bases/distrito/{distrito_id}/bases', [App\Http\Controllers\Publico\BaseController::class, 'getBasesPorDistrito']);
    Route::get('publico/sectores/{id}/bases', [App\Http\Controllers\Publico\BaseController::class, 'getBases']);

    // ==========================================
    // MANTENIMIENTO (Regiones, Provincias, Distritos, Sectores, Cargos, Conflictos, Turnos, Denuncias)
    // ==========================================
    Route::resource('publico/regiones', App\Http\Controllers\Publico\RegionController::class);
    Route::resource('publico/provincias', App\Http\Controllers\Publico\ProvinciaController::class);
    Route::resource('publico/distritos', App\Http\Controllers\Publico\DistritoController::class);
    Route::resource('publico/sectores', App\Http\Controllers\Publico\SectorController::class);
    Route::post('publico/sectores/eliminar-masivo', [App\Http\Controllers\Publico\SectorController::class, 'eliminarMasivo']);
    Route::resource('publico/cargos', App\Http\Controllers\Publico\CargoController::class);
    Route::resource('publico/conflictos', App\Http\Controllers\Publico\ConflictoController::class);
    Route::resource('publico/turnos', App\Http\Controllers\Publico\TurnoController::class);
    Route::resource('publico/denuncias', App\Http\Controllers\Publico\DenunciaController::class);

    // ==========================================
    // RELACIONES GEOGRÁFICAS
    // ==========================================
    // Provincias por región
    Route::get('publico/regiones/{id}/provincias', [App\Http\Controllers\Publico\ProvinciaController::class, 'getByRegion']);

    // Distritos por provincia
    Route::get('publico/provincias/{id}/distritos', [App\Http\Controllers\Publico\DistritoController::class, 'getDistritosPorProvincia']);

    // Sectores por distrito
    Route::get('publico/distritos/{id}/sectores', [App\Http\Controllers\Publico\DistritoController::class, 'getSectores']);

    // ==========================================
    // CONSULTAS RENIEC Y REQUISITORIADOS
    // ==========================================
    Route::get('getdatadni/{dni}/{tipo_persona}', [App\Http\Controllers\UtilsController::class, 'getDataDNI']);
    Route::get('publico/ronderos/buscar-dni/{dni}', [App\Http\Controllers\Publico\RonderoController::class, 'buscarPorDNI']);
    Route::post('consultar-rq', [App\Http\Controllers\UtilsController::class, 'consultarRQ']);

    // ==========================================
    // AUDITORÍA
    // ==========================================
    Route::get('admin/auditoria', [App\Http\Controllers\Admin\AuditoriaController::class, 'index']);
    Route::get('admin/auditoria/estadisticas', [App\Http\Controllers\Admin\AuditoriaController::class, 'estadisticas']);

    // ==========================================
    // CONFIGURACIONES (solo SuperAdministrador)
    // ==========================================
    Route::middleware(['auth:sanctum'])->prefix('admin/configuracion')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'getAll']);
        Route::post('/reniec', [ConfiguracionController::class, 'updateReniec']);
        Route::post('/consulta-rq', [ConfiguracionController::class, 'updateConsultaRQ']);
        Route::post('/defaults', [ConfiguracionController::class, 'seedDefaults']);
        Route::post('/reniec/actualizar-credencial', [ConfiguracionController::class, 'actualizarCredencial']);
    });

    // ==========================================
    // INCLUIR OTROS ARCHIVOS DE RUTAS
    // ==========================================
    require_once __DIR__ . '/api_h.php';
    require_once __DIR__ . '/apiexports.php';
});
