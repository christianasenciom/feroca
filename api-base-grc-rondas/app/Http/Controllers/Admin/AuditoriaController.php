<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Auditoria::with(['usuario', 'rondero.persona', 'region', 'provincia', 'distrito', 'sector', 'base'])
                ->orderBy('created_at', 'desc');
            
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
            \Log::error('Error en index auditoría: ' . $e->getMessage());
            return response()->json([
                'data' => [],
                'meta' => ['total' => 0]
            ]);
        }
    }
    
    public function estadisticas()
    {
        try {
            $totalConsultas = Auditoria::count();
            $consultasReniec = Auditoria::where('tipo_consulta', 'reniec')->count();
            $consultasRequisitoriados = Auditoria::where('tipo_consulta', 'requisitoriado')->count();
            $consultasExitosas = Auditoria::where('encontrado', true)->count();
            $consultasFallidas = Auditoria::where('encontrado', false)->count();
            
            // Consultas por tipo
            $porTipo = Auditoria::select('tipo_consulta', DB::raw('count(*) as total'))
                ->groupBy('tipo_consulta')
                ->get();
            
            // Consultas por día (últimos 7 días)
            $porDia = Auditoria::select(DB::raw('DATE(created_at) as fecha'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('fecha', 'desc')
                ->get();
            
            $estadisticas = [
                'total_consultas' => $totalConsultas,
                'consultas_reniec' => $consultasReniec,
                'consultas_requisitoriados' => $consultasRequisitoriados,
                'consultas_exitosas' => $consultasExitosas,
                'consultas_fallidas' => $consultasFallidas,
                'por_tipo' => $porTipo,
                'por_dia' => $porDia
            ];
            
            return response()->json($estadisticas);
            
        } catch (\Exception $e) {
            \Log::error('Error en estadisticas auditoría: ' . $e->getMessage());
            return response()->json([
                'total_consultas' => 0,
                'consultas_reniec' => 0,
                'consultas_requisitoriados' => 0,
                'consultas_exitosas' => 0,
                'consultas_fallidas' => 0,
                'por_tipo' => [],
                'por_dia' => []
            ]);
        }
    }
}