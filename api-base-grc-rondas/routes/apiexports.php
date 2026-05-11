<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PermissionController;

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('auth/exportar/permisos', [PermissionController::class, 'exportar']);
});
