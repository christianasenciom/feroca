<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Publico\Base;
use App\Models\Publico\Denuncia;
use App\Models\Publico\Rondero;
use App\Models\Publico\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function estadisticas(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || (!$usuario->hasRole('SuperAdministrador') && !$usuario->hasRole('Administrador'))) {
            return response()->json([
                'message' => 'No tiene permiso para ver este dashboard'
            ], Response::HTTP_FORBIDDEN);
        }

        $esSuper = $usuario->hasRole('SuperAdministrador');
        $actividadPage = max((int) $request->input('actividad_page', 1), 1);
        $actividadLimit = max((int) $request->input('actividad_limit', 10), 1);

        $queryRonderos = Rondero::query()->where('eliminado', false);
        $queryBases = Base::query()->where('eliminado', false);
        $querySectores = Sector::query()->where('eliminado', false);
        $queryDenuncias = Denuncia::query()->where('eliminado', false);

        $queryAuditoria = Auditoria::query();
        if (!$esSuper) {
            $queryAuditoria->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhereHas('usuario', function ($uq) {
                        $uq->whereDoesntHave('roles', function ($rq) {
                            $rq->where('name', 'SuperAdministrador');
                        });
                    });
            });
        }

        $estadisticas = [
            'rol' => $esSuper ? 'SuperAdministrador' : 'Administrador',
            'scope' => 'global',
            'bases_asignadas' => null,
            'ronderos' => [
                'total' => (clone $queryRonderos)->count(),
                'activos' => (clone $queryRonderos)->where('estado', true)->count(),
                'inactivos' => (clone $queryRonderos)->where('estado', false)->count(),
            ],
            'bases' => [
                'total' => (clone $queryBases)->count(),
                'activas' => (clone $queryBases)->where('estado', true)->count(),
                'inactivas' => (clone $queryBases)->where('estado', false)->count(),
            ],
            'sectores' => [
                'total' => (clone $querySectores)->count(),
                'activos' => (clone $querySectores)->where('estado', true)->count(),
                'inactivos' => (clone $querySectores)->where('estado', false)->count(),
            ],
            'denuncias' => [
                'total' => (clone $queryDenuncias)->count(),
                'pendientes' => (clone $queryDenuncias)->where('estado', true)->count(),
            ],
            'auditoria' => [
                'total' => (clone $queryAuditoria)->count(),
                'hoy' => (clone $queryAuditoria)->whereDate('created_at', now()->toDateString())->count(),
            ],
        ];

        $actividadReciente = (clone $queryAuditoria)
            ->with('usuario:id,name')
            ->orderByDesc('created_at')
            ->paginate($actividadLimit, ['*'], 'actividad_page', $actividadPage);

        $actividadData = collect($actividadReciente->items())
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'tipo' => $item->tipo_consulta,
                    'detalle' => $item->nombre_consultado,
                    'usuario' => optional($item->usuario)->name,
                    'fecha' => optional($item->created_at)->toDateTimeString(),
                    'exitoso' => (bool) $item->encontrado,
                ];
            });

        return response()->json([
            'estadisticas' => $estadisticas,
            'actividad_reciente' => $actividadData,
            'actividad_meta' => [
                'current_page' => $actividadReciente->currentPage(),
                'per_page' => $actividadReciente->perPage(),
                'total' => $actividadReciente->total(),
                'last_page' => $actividadReciente->lastPage(),
            ],
        ]);
    }
}



