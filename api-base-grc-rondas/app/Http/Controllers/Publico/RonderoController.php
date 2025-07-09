<?php
namespace App\Http\Controllers\Publico;

use App\Http\Requests\Admin\PersonaRequest;
use App\Http\Requests\Admin\RonderoRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\Admin\Auth\PersonaResource;
use App\Http\Resources\Admin\Auth\RonderoResource;
use App\Http\Resources\Publico\ComiteResource;
use App\Models\Publico\Rondero;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use App\Models\Role;
use App\Models\Base\User;
use App\Models\Publico\Persona;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\Admin\Auth\UserResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class RonderoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $ronderos = Rondero::query()->with('persona')->where('eliminado','=', false);

        if($keyword != '' && $keyword != null) {
            $ronderos =
                $ronderos->whereHas('persona',function($query) use ($keyword) {
                        $query->where('docIdentidad', 'ilike', '%' . $keyword . '%')
                            ->orWhereRaw("CONCAT(nombres, ' ', ' ', apellido_paterno, ' ', apellido_materno) ilike ?", ['%' . $keyword . '%'])
                            ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres, ' ') ilike ?", ['%' . $keyword . '%'])
                            ->orWhere('apellido_paterno', 'ilike', '%' . $keyword . '%')
                            ->orWhere('apellido_materno', 'ilike', '%' . $keyword . '%')
                            ->orWhere('nombres', 'ilike', '%' . $keyword . '%');
                    });
        }

        $ronderos = $ronderos->orderBy('id', 'desc');

//        Log::alert($ronderos->toRawSql());

        return RonderoResource::collection($ronderos->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RonderoRequest $request)
    {
        try {
            // Comenzamos una transacción para asegurarnos que ambos registros sean creados o no se realicen cambios en caso de algún error
            DB::beginTransaction();
            $base64Image = $request->input('foto');
            if (!empty($base64Image)) {
                // Decodificar la imagen base64
                $image = base64_decode($base64Image);
                // Crear un nombre de archivo único
                $imageName = 'imagen_' . time() . '.jpg';
                // Guardar la imagen en el disco
                $directory = '/fotos_personas/';
                Storage::disk('files_rondas')->put($directory.'/'.$imageName, $image);

            }

            // Verificamos si ya existe un usuario con el DNI proporcionado
            $persona_existe = Persona::where('docIdentidad', $request->input('docIdentidad'))->exists();

            // Si la persona no existe, se crea
            if(!$persona_existe) {
                // Si no existe la persona, creamos una nueva
                $persona = new Persona();
                $persona->documento_tipo = $request->input('documento_tipo') ?? 'DNI';
                $persona->apellido_paterno = $request->input('apellido_paterno');
                $persona->apellido_materno = $request->input('apellido_materno');
                $persona->nombres = $request->input('nombres');
                $persona->docIdentidad = $request->input('docIdentidad');
                $persona->tipo = $request->input('tipo') ?? 'Natural';
            }
            else {
                // Si existe la persona
                $persona = Persona::query()->where('docIdentidad', $request->input('docIdentidad'))->first();
            }
            $persona->fecha_nacimiento = $request->input('fecha_nacimiento');
            $persona->genero = $request->input('genero');
            $persona->email = $request->email ?? $persona->email;
            $persona->direccion = $request->input('direccion');
            $persona->celular = $request->input('celular');
            $persona->foto = $imageName ?? '';
            $persona->save();


            // Registrar el rondero
            $ronderoData = $request->only([
                'fecha_inicio',
                'fecha_cese',
                'region_id',
                'provincia_id',
                'distrito_id',
                'sector_zona_id',
                'base_id',
                'partida_registral'
            ]);
            $ronderoData['codigo_qr'] = (string)Str::uuid();
            $ronderoData['persona_id'] = $persona->id;
            $rondero = Rondero::create($ronderoData);

            // Finalizamos la transacción
            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Rondero registrado correctamente',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new RonderoResource(Rondero::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RonderoRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

//            $base64Image = $request->input('persona.foto');
//            if (!empty($base64Image)) {
//                // Decodificar la imagen base64
//                $image = base64_decode($base64Image);
//                // Crear un nombre de archivo único
//                $imageName = 'imagen_' . time() . '.jpg';
//                // Guardar la imagen en el disco
//                $directory = '/fotos_personas/';
//                Storage::disk('files_rondas')->put($directory.'/'.$imageName, $image);
//
//            }

            // Actualizar RONDERO
            $rondero = Rondero::query()->findOrFail($id);
            $rondero->fecha_inicio = $request->input('fecha_inicio');
            $rondero->fecha_cese = $request->input('fecha_cese');
            $rondero->estado = $request->input('estado');
            $rondero->region_id = $request->input('region_id');
            $rondero->provincia_id = $request->input('provincia_id');
            $rondero->distrito_id = $request->input('distrito_id');
            $rondero->sector_zona_id = $request->input('sector_zona_id');
            $rondero->base_id = $request->input('base_id');
            $rondero->partida_registral = $request->input('partida_registral');

            $rondero->save();

            // Actualizar PERSONA
            $persona = Persona::query()->findOrFail($rondero->persona_id);
            $persona->fecha_nacimiento = $request->input('persona.fecha_nacimiento');
            $persona->genero = $request->input('persona.genero');
            $persona->direccion = $request->input('persona.direccion');
            $persona->celular = $request->input('persona.celular');
            $persona->email = $request->input('persona.email');
//            $persona->foto = $imageName ?? '';
            $persona->save();



            DB::commit();
            $response = [
                "state" => "success",
                "message" => "El registro se actualizo correctamente.",
            ];
            return response()->json($response,Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $rondero = Rondero::query()->findOrFail($id);
            $rondero->eliminado = true;
            $rondero->save();

            $response = [
                "state" => "success",
                "message" => "Registro eliminado",
            ];
            return response()->json($response,Response::HTTP_OK);

//            $user->delete();

        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.rondero.eliminar', 'api')) {

            $rondero = Rondero::query()->findOrFail($id);

            if ($rondero != null) {
                DB::beginTransaction();
                try {
                    $rondero->estado = true;
                    $rondero->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $rondero], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }
    }

    public function inactivar($id)
    {

        if (auth()->user()->hasPermissionTo('pub.rondero.eliminar', 'api')) {

            $rondero = Rondero::query()->findOrFail($id);

            if ($rondero != null) {
                DB::beginTransaction();
                try {
                    $rondero->estado = false;
                    $rondero->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro inactivado", 'data' => $rondero], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }
    }

    public function generarCarnet(Request $request)
    {
        $data = [];
        $id = $request->input('id');
        $url_front = $request->input('url_front');
        if($id != null) {
            $rondero = Rondero::query()->findOrFail($id);
            // Texto QR
            $texto_qr = $url_front .'/validar-qr?qr='. $rondero->codigo_qr;
            // Create QR code
            $writer = new PngWriter();
            $qrCode = QrCode::create($texto_qr)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->setSize(300)
                ->setMargin(10)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            // Genera la imagen del QR y guarda el archivo
            $fileName = 'qr_code_' . uniqid() . '.png';
            $result = $writer->write($qrCode);
            $dataUri = $result->getDataUri();

//            dd(base64_encode(file_get_contents(public_path('files_rondas/fotos_personas/'. $rondero->persona->foto))));
            // Ruta de la imagen en la carpeta pública o ruta simbólica
//            $data['foto'] = asset('files_rondas/fotos_personas/'.$rondero->persona->foto);
			$ruta_foto = public_path('files_rondas/fotos_personas/'. $rondero->persona->foto);
			$foto_base_64 = "";
			if (file_exists($ruta_foto)) {
				$foto_base_64 = base64_encode(file_get_contents($ruta_foto));
			}
            $data['foto'] = $foto_base_64;
            $data['apellido_paterno'] = $rondero->persona->apellido_paterno;
            $data['apellido_materno'] = $rondero->persona->apellido_materno;
            $data['nombres'] = $rondero->persona->nombres;
            $data['numero'] = $rondero->persona->docIdentidad;
            $data['qrCodePath'] = $dataUri;
            $data['cargos'] = $rondero->comites->pluck('cargo.descripcion')->toArray();
            $data['region'] = $rondero->region->descripcion;
            $data['provincia'] = $rondero->provincia->descripcion;
            $data['distrito'] = $rondero->distrito->descripcion;
            $data['sector_zona'] = $rondero->sector_zona->descripcion;
            $data['base'] = $rondero->base->descripcion;
        }else{
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }

        $pdf = PDF::loadView('carnet', $data);


        // Establecer tamaño personalizado de 85 mm x 54 mm para el carnet
//        $pdf->setPaper([0, 0, 8.44, 13.15], 'portrait');

        return $pdf->download('carnet.pdf');
    }
}
