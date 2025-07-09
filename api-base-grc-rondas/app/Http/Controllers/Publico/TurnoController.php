<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Http\Resources\GlobalResource;
use App\Models\Publico\ArchivosAsamblea;
use App\Models\Publico\Turno;
use App\Models\Publico\ProgramacionTurno;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\Publico\TurnoResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class TurnoController extends Controller
{
    public function index(Request $request)
    {

        $keyword = $request->keyword;
        $tipo_reunion = $request->tipo_reunion;

        $turnos = Turno::query()->with('base')->where('eliminado','=', false);
        $turnos = $turnos->where('tipo_reunion','=', $tipo_reunion);

        if($keyword != '' && $keyword != null) {
            $turnos = $turnos->whereHas('base', function($query) use ($keyword) {
                $query->where('descripcion', 'ILIKE', "%{$keyword}%");
            })->orWhereRaw("to_char(fecha, 'DD/MM/YYYY') ILIKE '%$keyword%'")
            ->when($tipo_reunion === 'Vigilancia', function ($query) use ($keyword) {
                $query->orWhereHas('responsable.persona', function($query) use ($keyword) {
                    $query->whereRaw("CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) ILIKE '%$keyword%'");
                });
            });
        }


        $turnos = $turnos->orderBy('id', 'desc');

        return TurnoResource::collection($turnos->paginate($request->limit));
    }

    public function show($id)
    {
        return new TurnoResource(Turno::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $turno = Turno::find($id);

        try {

            DB::beginTransaction();


                $validated = $request->validate([
                    'responsable_turno' => $request->tipo_reunion === 'Vigilancia' ? 'required|exists:rondero,id' : 'nullable',
                    'tipo_reunion' => 'required',
                    'base_id' => 'required|exists:base,id',
                    'sector_zona_id' => 'required|exists:sector_zona,id',
                    'distrito_id' => 'required|exists:distrito,id',
                    'provincia_id' => 'required|exists:provincia,id',
                    'region_id' => 'required|exists:region,id',
                    'descripcion' => 'nullable|string',
                    'fecha' => 'required|date',
                ]);


                $turno->descripcion = $request->descripcion;
                $turno->responsable_turno = $request->responsable_turno;
                $turno->base_id = $request->base_id;
                $turno->sector_zona_id = $request->sector_zona_id;
                $turno->region_id = $request->region_id;
                $turno->distrito_id = $request->distrito_id;
                $turno->provincia_id = $request->provincia_id;
                $turno->fecha = $request->fecha;
                $turno->tipo_reunion = $request->tipo_reunion;
                $turno->save();

                $ronderos = $request->input('ronderos');

                if(empty($ronderos)){
                    return response()->json('no existen ronderos');
                }

                $validator = Validator::make($request->all(),[
                    'ronderos' => 'required|array',
                    'ronderos.*.rondero_id' => 'required|integer|exists:rondero,id'
                ]);

                if($validator->fails()){
                    return response()->json('Error');
                }

                DB::table('programacion_turno')->where('turno_id', $id)->delete();

                foreach($ronderos as $rondero){
                    $turnoVigilancia = new ProgramacionTurno();
                    $turnoVigilancia->rondero_id = $rondero["rondero_id"];
                    $turnoVigilancia->turno_id = $id;
                    $turnoVigilancia->save();
                    Log::alert($turnoVigilancia);
                }
                DB::commit();
                return response()->json([
                    'state' => 'success',
                    'message' => 'Turno actualizado correctamente',
                ], Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function updateAsistencia(Request $request, $id)
    {
        $turno = Turno::find($id);

        try {

            DB::beginTransaction();


            $validated = $request->validate([
                'descripcion' => 'nullable|string',
            ]);


            $turno->descripcion = $request->descripcion;
            $turno->save();

            $ronderos = $request->input('ronderos');

            if(empty($ronderos)){
                return response()->json('no existen ronderos');
            }

            $validator = Validator::make($request->all(),[
                'ronderos' => 'required|array',
                'ronderos.*.rondero_id' => 'required|integer|exists:rondero,id'
            ]);

            if($validator->fails()){
                return response()->json('Error');
            }

            foreach($ronderos as $rondero){
                $turnoVigilancia = ProgramacionTurno::where('rondero_id','=',$rondero["rondero_id"])->where('turno_id','=',$id)->first();
                $turnoVigilancia->tipo_asistencia = $rondero["tipo_asistencia"];
                $turnoVigilancia->observaciones = $rondero["observaciones"];
                $turnoVigilancia->save();
//                Log::alert($turnoVigilancia);
            }
            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Turno actualizado correctamente',
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy(string $id)
    {

        try {

            $turno = Turno::query()->findOrFail($id);
            $turno->eliminado = 1;
            $turno->deleted_by = auth()->user()->id;
            $turno->save();

            $listaRonderos = ProgramacionTurno::where('turno_id','=',$id)->get();
            foreach($listaRonderos as $rondero){
                $rondero->eliminado = 1;
                $rondero->deleted_by = auth()->user()->id;
                $rondero->save();
            }


            $response = [
                "state" => "success",
                "message" => "Registro eliminado",
            ];
            return response()->json($response,Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function store(Request $request)
    {

        try {

            DB::beginTransaction();

                $validated = $request->validate([
                    'responsable_turno' => $request->tipo_reunion === 'Vigilancia' ? 'required|exists:rondero,id' : 'nullable',
                    'tipo_reunion' => 'required',
                    'base_id' => 'required|exists:base,id',
                    'sector_zona_id' => 'required|exists:sector_zona,id',
                    'distrito_id' => 'required|exists:distrito,id',
                    'provincia_id' => 'required|exists:provincia,id',
                    'region_id' => 'required|exists:region,id',
                    'descripcion' => 'nullable|string',
                    'fecha' => 'required|date',
                ]);

                $turno = Turno::create($validated);

                $idTurno = $turno->id;
                $ronderos = $request->input('ronderos');

                if(empty($ronderos)){
                    return response()->json('no existen ronderos');
                }

                $validator = Validator::make($request->all(),[
                    'ronderos' => 'required|array',
                    'ronderos.*.rondero_id' => 'required|integer|exists:rondero,id'
                ]);

                if($validator->fails()){
                    return response()->json('Error');
                }

                foreach($ronderos as $rondero){
                    $turnoVigilancia = new ProgramacionTurno();
                    $turnoVigilancia->rondero_id = $rondero["rondero_id"];
                    $turnoVigilancia->turno_id = $idTurno;
                    $turnoVigilancia->save();
                    Log::alert($turnoVigilancia);
                }
                DB::commit();
                return response()->json([
                    'state' => 'success',
                    'message' => 'Turno registrado correctamente',
                ], Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ronderosPorTurno($idTurno){

        try {

            $turno = Turno::with('listaRonderos')->where('id','=',$idTurno)->where('eliminado', false)->first();

            if($turno != null && $turno != ''){
                return response()->json($turno);
            }
            else{
                return response()->json([
                    'state' => 'success',
                    'message' => 'No se ha encontrado el turno seleccionado.',
                ], Response::HTTP_OK);
            }


        } catch(Exception $e){

        }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.gruposvigilancia.activar', 'api')) {

            $turno = Turno::query()->where('id','=',$id)->where('estado',false)->first();

            if ($turno) {
                DB::beginTransaction();
                try {
                    $turno->estado = true;
                    $turno->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $turno], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado o ya se enuentra activado"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }
    }

    public function desactivar($id)
    {

        if (auth()->user()->hasPermissionTo('pub.gruposvigilancia.desactivar', 'api')) {

            $turno = Turno::query()->where('id','=',$id)->where('estado',true)->first();

            if ($turno) {
                DB::beginTransaction();
                try {
                    $turno->estado = false;
                    $turno->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $turno], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado o ya se encuentra desactivado"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }
    }

    public function subirImagenAsamblea(string $id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }
            $disk = 'files_rondas';
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $filename = Carbon::now()->timestamp . '.' . $extension;
                // Comprimir la imagen
                $imageCompressed = Image::make($file->getRealPath());
                $imageCompressed->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode('jpg', 75); // Ajustar la calidad de la imagen
                    $directory = 'documentos_asambleas/'. $id;
                $imagePath = Storage::disk($disk)->put($directory.'/'.$filename, $imageCompressed->stream());

                ArchivosAsamblea::create([
                    'turno_id' => $id,
                    'ruta_archivo' =>  $directory . '/' . $filename,
                ]);


                return response()->json([
                    'success' => true,
                    'message' => 'Imagen subida correctamente',
                ], Response::HTTP_OK);
            }

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['success' => false, 'message' => 'No file uploaded', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
