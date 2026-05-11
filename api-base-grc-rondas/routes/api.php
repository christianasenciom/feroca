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

    // Auth
    Route::get('auth/webuser', [WebAuthController::class, 'UserInfo']);
    Route::post('auth/weblogout', [WebAuthController::class, 'SingOutCurrentSession']);
    Route::post('auth/weblogoutall', [WebAuthController::class, 'SingOutAllSessions']);
    Route::post('auth/newpassword', [WebAuthController::class, 'passwordResset']);
    Route::post('auth/updateimage', [WebAuthController::class, 'updateImage']);
    Route::post('auth/initnewpassword', [WebAuthController::class, 'passwordRessetInit']);

    // Usuarios y permisos
    Route::resource('auth/permisos', App\Http\Controllers\Auth\PermissionController::class);
    Route::get('auth/permisosall', [App\Http\Controllers\Auth\PermissionController::class, 'all']);
    Route::get('permissionsforrole', [App\Http\Controllers\Auth\PermissionController::class, 'fetchPermissionsForRole']);
    Route::resource('auth/roles', App\Http\Controllers\Auth\RoleController::class);
    Route::get('auth/rolesall', [App\Http\Controllers\Auth\RoleController::class, 'all']);
    Route::post('syncRolePermissions', [App\Http\Controllers\Auth\RoleController::class, 'syncRolePermissions']);
    Route::resource('auth/usuarios', App\Http\Controllers\Auth\UserController::class);

    // Personas
    Route::resource('publico/personas', App\Http\Controllers\Publico\PersonaController::class);

    // Ronderos
    Route::resource('publico/ronderos', App\Http\Controllers\Publico\RonderoController::class);
    Route::post('publico/ronderos/{id}/activar', [App\Http\Controllers\Publico\RonderoController::class, 'activar']);
    Route::post('publico/ronderos/{id}/desactivar', [App\Http\Controllers\Publico\RonderoController::class, 'inactivar']);
    Route::post('generar-carnet', [App\Http\Controllers\Publico\RonderoController::class, 'generarCarnet']);
    Route::get('publico/ronderos/{id}/foto', [App\Http\Controllers\Publico\RonderoController::class, 'getFoto']);

    // Bases
    Route::get('publico/bases/ronderos', [App\Http\Controllers\Publico\BaseController::class, 'getRonderosByBase']);

    // Consultas RENIEC y REQUISITORIADOS
    Route::get('getdatadni/{dni}/{tipo_persona}', [App\Http\Controllers\UtilsController::class, 'getDataDNI']);
    Route::post('consultar-rq', [App\Http\Controllers\UtilsController::class, 'consultarRQ']);

    // Auditoría
    Route::get('admin/auditoria', [App\Http\Controllers\Admin\AuditoriaController::class, 'index']);
    Route::get('admin/auditoria/estadisticas', [App\Http\Controllers\Admin\AuditoriaController::class, 'estadisticas']);

    // ==========================================
    // CONFIGURACIONES (solo SuperAdministrador)
    // ==========================================
    //Route::middleware(['role:SuperAdministrador'])->prefix('admin/configuracion')->group(function () {
    //    Route::get('/reniec', [ConfiguracionController::class, 'getReniecConfig']);
    //    Route::post('/reniec', [ConfiguracionController::class, 'updateReniec']);
    //    Route::post('/reniec/defaults', [ConfiguracionController::class, 'seedDefaults']);
    //    Route::post('/reniec/test', [ConfiguracionController::class, 'testConnection']);
    //});

    // Incluir otros archivos de rutas
    require_once __DIR__ . '/api_h.php';
    require_once __DIR__ . '/apiexports.php';
});
