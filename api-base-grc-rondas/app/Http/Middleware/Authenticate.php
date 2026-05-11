<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // 🔥 Para cualquier ruta que comience con api/ - NO redirigir
        if ($request->is('api/*')) {
            return null;
        }
        
        // 🔥 Específicamente para rutas de comités (con o sin api/)
        if ($request->is('api/publico/comites/*') || $request->is('publico/comites/*')) {
            return null;
        }

        // 🔥 Específicamente para rutas de ronderos potenciales administradores
        if ($request->is('api/publico/ronderos/potenciales-administradores')) {
            return null;
        }
        
        return $request->expectsJson() ? null : route('login');
    }
}