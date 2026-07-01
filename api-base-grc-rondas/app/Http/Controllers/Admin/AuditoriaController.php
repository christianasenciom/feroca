<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditoriaController extends Controller
{
    public function limpiarTodo(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->hasRole('SuperAdministrador')) {
            return response()->json([
                'message' => 'Solo el SuperAdministrador puede limpiar la auditoría'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $eliminados = Auditoria::query()->count();
            Auditoria::query()->delete();

            return response()->json([
                'state' => 'success',
                'message' => 'Auditoría limpiada correctamente',
                'deleted_count' => $eliminados,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error limpiando auditoría: ' . $e->getMessage());

            return response()->json([
                'state' => 'error',
                'message' => 'No se pudo limpiar la auditoría'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index(Request $request)
    {
        try {
            $usuario = $request->user();
            if (!$usuario || (!$usuario->hasRole('SuperAdministrador') && !$usuario->hasRole('Administrador'))) {
                return response()->json([
                    'message' => 'No tiene permiso para ver la auditoría'
                ], Response::HTTP_FORBIDDEN);
            }

            $query = Auditoria::with(['usuario:id,name'])
                ->orderBy('created_at', 'desc');

            $query = $this->aplicarVisibilidadPorRol($query, $usuario);

            // Filtros
            if ($request->tipo_consulta) {
                $query->where('tipo_consulta', $request->tipo_consulta);
            }

            if ($request->dni) {
                $query->where('dni_consultado', 'like', '%' . $request->dni . '%');
            }

            if ($request->nombre) {
                $query->where('nombre_consultado', 'like', '%' . $request->nombre . '%');
            }

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->base_id) {
                $query->where('base_id', $request->base_id);
            }

            if ($request->fecha_desde) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->fecha_hasta) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $perPage = $request->limit ?? 15;
            $auditorias = $query->paginate($perPage);

            return response()->json([
                'data' => $auditorias->items(),
                'meta' => [
                    'current_page' => $auditorias->currentPage(),
                    'per_page' => $auditorias->perPage(),
                    'total' => $auditorias->total(),
                    'last_page' => $auditorias->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en index auditoría: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'data' => [],
                'meta' => ['total' => 0]
            ]);
        }
    }

    public function estadisticas()
    {
        try {
            $usuario = request()->user();
            if (!$usuario || (!$usuario->hasRole('SuperAdministrador') && !$usuario->hasRole('Administrador'))) {
                return response()->json([
                    'message' => 'No tiene permiso para ver la auditoría'
                ], Response::HTTP_FORBIDDEN);
            }

            $baseQuery = $this->aplicarVisibilidadPorRol(Auditoria::query(), $usuario);

            $totalConsultas = (clone $baseQuery)->count();
            $consultasReniec = (clone $baseQuery)->where('tipo_consulta', 'reniec')->count();
            $consultasRequisitoriados = (clone $baseQuery)->where('tipo_consulta', 'requisitoriado')->count();
            $consultasMovimientos = (clone $baseQuery)->where('tipo_consulta', 'movimiento')->count();
            $consultasExitosas = (clone $baseQuery)->where('encontrado', true)->count();
            $consultasFallidas = (clone $baseQuery)->where('encontrado', false)->count();

            // Consultas por tipo
            $porTipo = (clone $baseQuery)->select('tipo_consulta', DB::raw('count(*) as total'))
                ->groupBy('tipo_consulta')
                ->get();

            // Consultas por día (últimos 7 días)
            $porDia = (clone $baseQuery)->select(DB::raw('DATE(created_at) as fecha'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('fecha', 'desc')
                ->get();

            $estadisticas = [
                'total_consultas' => $totalConsultas,
                'consultas_reniec' => $consultasReniec,
                'consultas_requisitoriados' => $consultasRequisitoriados,
                'consultas_movimientos' => $consultasMovimientos,
                'consultas_exitosas' => $consultasExitosas,
                'consultas_fallidas' => $consultasFallidas,
                'por_tipo' => $porTipo,
                'por_dia' => $porDia
            ];

            return response()->json($estadisticas);

        } catch (\Exception $e) {
            Log::error('Error en estadisticas auditoría: ' . $e->getMessage());
            return response()->json([
                'total_consultas' => 0,
                'consultas_reniec' => 0,
                'consultas_requisitoriados' => 0,
                'consultas_movimientos' => 0,
                'consultas_exitosas' => 0,
                'consultas_fallidas' => 0,
                'por_tipo' => [],
                'por_dia' => []
            ]);
        }
    }

    private function aplicarVisibilidadPorRol($query, $usuario)
    {
        // SuperAdministrador ve todo
        if ($usuario->hasRole('SuperAdministrador')) {
            return $query;
        }

        // Administrador: todo excepto acciones de SuperAdministrador
        if ($usuario->hasRole('Administrador')) {
            return $query->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhereHas('usuario', function ($uq) {
                        $uq->whereDoesntHave('roles', function ($rq) {
                            $rq->where('name', 'SuperAdministrador');
                        });
                    });
            });
        }

        // Otros roles no deben ver registros
        return $query->whereRaw('1 = 0');
    }
}
