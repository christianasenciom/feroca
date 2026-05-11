<?php

namespace App\Http\Controllers\Publico;

use App\Exports\Reportes\AsambleasFechasExport;
use App\Exports\Reportes\DenunciasCriteriosExport;
use App\Exports\Reportes\DenunciasFechasExport;
use App\Exports\Reportes\DenunciasPersonaExport;
use App\Http\Resources\Publico\Reportes\AsambleasFechasResource;
use App\Http\Resources\Publico\Reportes\DenunciasFechasResource;
use App\Models\Publico\Base;
use App\Models\Publico\Denuncia;
use App\Models\Publico\Denunciado;
use App\Models\Publico\Distrito;
use App\Models\Publico\Provincia;
use App\Models\Publico\Region;
use App\Models\Publico\Sector;
use App\Models\Publico\Turno;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\GlobalResource;
use App\Models\Publico\Persona;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
    public function denunciasPorFechas(Request $request)
    {

            // Recupera las fechas de inicio y fin
            $fechainicio = new Carbon($request->dates[0]);
            $fechaFin = (new Carbon($request->dates[1]))->endOfDay();


            // Valida que las fechas no sean nulas
            if ($fechainicio == null || $fechaFin == null) {
                return response()->json([
                    'message' => 'Debe ingresar un rango de fechas'
                ], Response::HTTP_BAD_REQUEST);
            }

            $denuncias = Denuncia::query();
            if ($fechainicio && $fechaFin) {
                $denuncias = $denuncias->whereBetween('fecha', [$fechainicio, $fechaFin]);
            }

            $denuncias = $denuncias->with('denunciante', 'conflicto')->get();


            return DenunciasFechasResource::collection($denuncias);

    }

    public function denunciasPorFechasExcel(Request $request)
    {

            $fechainicio = new Carbon($request->dates[0]);
            $fechaFin = (new Carbon($request->dates[1]))->endOfDay();

            // Valida que las fechas no sean nulas
            if ($fechainicio == null || $fechaFin == null) {
                return response()->json([
                    'message' => 'Debe ingresar un rango de fechas'
                ], Response::HTTP_BAD_REQUEST);
            }

            return Excel::download(new DenunciasFechasExport($fechainicio, $fechaFin), 'filename.xlsx');
    }
    public function denunciasPorPersona(Request $request)
    {

        $type = $request->type;
        $keyword = trim(strtoupper($request->keyword));

        if ($type == null || $keyword == null) {
            return response()->json([
                'message' => 'Debe enviar los parametros '
            ], Response::HTTP_BAD_REQUEST);
        }

        $denuncias = collect();
        switch ($type) {
            case 'DENUNCIADO':
                $denunciado = Denunciado::whereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres) iLIKE ?", ["%$keyword%"])->first();

                if ($denunciado) {
                    $denuncias = Denuncia::whereHas('listaDenunciados.denunciado', function($q) use ($denunciado) {
                        $q->where('denunciado_id', $denunciado->id);
                    })->with('denunciante', 'conflicto')->get();
                } else {
                    $denuncias = collect(); // Empty collection if no denunciado found
                }
                break;
            case 'DENUNCIANTE':
                $denunciante = Persona::where('docIdentidad', $keyword)->first();

                if ($denunciante) {
                    $denuncias = $denunciante->denuncias()->with('denunciante', 'conflicto')->get();
                } else {
                    $denuncias = collect(); // Empty collection if no denunciante found
                }
            default:
                break;
        }

            return DenunciasFechasResource::collection($denuncias);

    }

    public function denunciasPorPersonaExcel(Request $request)
    {

        $type = $request->type;
        $keyword = trim(strtoupper($request->keyword));

        if ($type == null || $keyword == null) {
            return response()->json([
                'message' => 'Debe enviar los parametros '
            ], Response::HTTP_BAD_REQUEST);
        }

        return Excel::download(new DenunciasPersonaExport($type, $keyword), 'filename.xlsx');
    }

    public function obtenerDatosPorTipo(Request $request)
    {
        $tipo = $request->tipo;

        if ($tipo == null) {
            return response()->json([
                'message' => 'Debe enviar los parametros'
            ], Response::HTTP_BAD_REQUEST);
        }

        $datos = collect();
        switch ($tipo) {
            case 'base':
                $datos = Base::query()->get();
                break;
            case 'zona':
                $datos = Sector::query()->get();
                break;
            case 'distrito':
                $datos = Distrito::query()->get();
                break;
            case 'provincia':
                $datos = Provincia::query()->get();
                break;
            case 'region':
                $datos = Region::query()->get();
                break;
            default:
                return response()->json([
                    'message' => 'Tipo no valido'
                ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json($datos, Response::HTTP_OK);
    }

    public function denunciasPorCriterios(Request $request)
    {
        $fechaInicio = new Carbon($request->dates[0]);
        $fechaFin = (new Carbon($request->dates[1]))->endOfDay();
        $tipo = $request->tipo;
        $tipoId = $request->id_tipo;

        if ($fechaInicio == null || $fechaFin == null || $tipo == null || $tipoId == null) {
            return response()->json([
                'message' => 'Debe enviar los parametros'
            ], Response::HTTP_BAD_REQUEST);
        }

        $denuncias = Denuncia::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where(function ($query) use ($tipo, $tipoId) {
                switch ($tipo) {
                    case 'base':
                        $query->where('base_id', $tipoId);
                        break;
                    case 'zona':
                        $query->where('sector_zona_id', $tipoId);
                        break;
                    case 'distrito':
                        $query->where('distrito_id', $tipoId);
                        break;
                    case 'provincia':
                        $query->where('provincia_id', $tipoId);
                        break;
                    case 'region':
                        $query->where('region_id', $tipoId);
                        break;
                    default:
                        break;
                }
            })
            ->get();

        return DenunciasFechasResource::collection($denuncias);
    }

    public function denunciasPorCriteriosExcel(Request $request)
    {

        $fechaInicio = new Carbon($request->dates[0]);
        $fechaFin = (new Carbon($request->dates[1]))->endOfDay();
        $tipo = $request->tipo;
        $tipoId = $request->id_tipo;

        if ($fechaInicio == null || $fechaFin == null || $tipo == null || $tipoId == null) {
            return response()->json([
                'message' => 'Debe enviar los parametros'
            ], Response::HTTP_BAD_REQUEST);
        }

        return Excel::download(new DenunciasCriteriosExport($fechaInicio, $fechaFin, $tipo, $tipoId), 'filename.xlsx');
    }

    public function asambleasPorFechas(Request $request)
    {

        // Recupera las fechas de inicio y fin
        $fechainicio = new Carbon($request->dates[0]);
        $fechaFin = (new Carbon($request->dates[1]))->endOfDay();


        // Valida que las fechas no sean nulas
        if ($fechainicio == null || $fechaFin == null) {
            return response()->json([
                'message' => 'Debe ingresar un rango de fechas'
            ], Response::HTTP_BAD_REQUEST);
        }

        $asambleas = Turno::query()->where('tipo_reunion', 'Asamblea');
        if ($fechainicio && $fechaFin) {
            $asambleas = $asambleas->whereBetween('fecha', [$fechainicio, $fechaFin])->get();
        }

        return AsambleasFechasResource::collection($asambleas);

    }

    public function asambleasPorFechasExcel(Request $request)
    {

        $fechainicio = new Carbon($request->dates[0]);
        $fechaFin = (new Carbon($request->dates[1]))->endOfDay();

        // Valida que las fechas no sean nulas
        if ($fechainicio == null || $fechaFin == null) {
            return response()->json([
                'message' => 'Debe ingresar un rango de fechas'
            ], Response::HTTP_BAD_REQUEST);
        }

        return Excel::download(new AsambleasFechasExport($fechainicio, $fechaFin), 'filename.xlsx');
    }

}
