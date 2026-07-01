<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarMovimientosAuditoria
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('audit_started_at', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (!$request->user() || $request->isMethod('OPTIONS')) {
            return;
        }

        // Evita ruido de rutas internas de auditoria y sesion
        if (
            $request->is('api/admin/auditoria*') ||
            $request->is('api/auth/webuser')
        ) {
            return;
        }

        $startedAt = (float) $request->attributes->get('audit_started_at', microtime(true));
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $sensitiveFields = [
            'password',
            'current_password',
            'new_password',
            'password_confirmation',
            'file',
            'foto',
        ];

        $payload = $request->except($sensitiveFields);

        $meta = [
            'metodo' => $request->method(),
            'ruta' => $request->path(),
            'url' => $request->fullUrl(),
            'accion' => optional($request->route())->getActionName(),
            'params' => $request->query(),
            'payload' => $payload,
            'status_code' => $response->getStatusCode(),
            'duracion_ms' => $durationMs,
        ];

        AuditoriaService::registrarMovimiento($meta, $response->getStatusCode() < 400);
    }
}

