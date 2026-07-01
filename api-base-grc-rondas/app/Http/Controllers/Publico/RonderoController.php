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
use App\Services\ReniecService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Spatie\Permission\Models\Permission;

class RonderoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
 * Display a listing of the resource.
 */
public function index(Request $request)
{
    $keyword = $request->keyword;

    // Obtener filtros de ubicación
    $region_id = $request->region_id;
    $provincia_id = $request->provincia_id;
    $distrito_id = $request->distrito_id;
    $sector_zona_id = $request->sector_zona_id;
    $base_id = $request->base_id_filtro; // Nota: usar base_id_filtro del frontend

    $ronderos = Rondero::query()->with(['persona', 'region', 'provincia', 'distrito', 'sector_zona', 'base'])->where('eliminado', false);

    // Filtro por nombre o DNI
    if($keyword != '' && $keyword != null) {
        $ronderos = $ronderos->whereHas('persona', function($query) use ($keyword) {
            $query->where('docIdentidad', 'ilike', '%' . $keyword . '%')
                ->orWhereRaw("CONCAT(nombres, ' ', ' ', apellido_paterno, ' ', apellido_materno) ilike ?", ['%' . $keyword . '%'])
                ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres, ' ') ilike ?", ['%' . $keyword . '%'])
                ->orWhere('apellido_paterno', 'ilike', '%' . $keyword . '%')
                ->orWhere('apellido_materno', 'ilike', '%' . $keyword . '%')
                ->orWhere('nombres', 'ilike', '%' . $keyword . '%');
        });
    }

    // ===== NUEVOS FILTROS DE UBICACIÓN =====

    // Filtro por Región
    if ($region_id && $region_id != '' && $region_id != 0) {
        $ronderos = $ronderos->where('region_id', $region_id);
    }

    // Filtro por Provincia
    if ($provincia_id && $provincia_id != '' && $provincia_id != 0) {
        $ronderos = $ronderos->where('provincia_id', $provincia_id);
    }

    // Filtro por Distrito
    if ($distrito_id && $distrito_id != '' && $distrito_id != 0) {
        $ronderos = $ronderos->where('distrito_id', $distrito_id);
    }

    // Filtro por Sector
    if ($sector_zona_id && $sector_zona_id != '' && $sector_zona_id != 0) {
        $ronderos = $ronderos->where('sector_zona_id', $sector_zona_id);
    }

    // Filtro por Base
    if ($base_id && $base_id != '' && $base_id != 0) {
        $ronderos = $ronderos->where('base_id', $base_id);
    }

    // Filtro por Base (el que ya existía)
    if ($request->base_id && $request->base_id != '' && $request->base_id != 0) {
        $ronderos = $ronderos->where('base_id', $request->base_id);
    }

    $ronderos = $ronderos->orderBy('id', 'desc');

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

        $imageName = $this->persistirFotoDesdeEntrada($request->input('foto'));

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
        } else {
            // Si existe la persona
            $persona = Persona::query()->where('docIdentidad', $request->input('docIdentidad'))->first();
        }

        $persona->fecha_nacimiento = $request->input('fecha_nacimiento');
        $persona->genero = $request->input('genero');
        $persona->email = $request->input('email', $persona->email ?? null);
        $persona->direccion = $request->input('direccion');
        $persona->celular = $request->input('celular');
        $persona->foto = $imageName ?: $this->normalizarNombreFoto($persona->foto ?? '');
        $persona->save();

        // 🔥 GENERAR CÓDIGO DE RONDERO AUTOMÁTICAMENTE
        $codigo_rondero = $this->generarCodigoRondero();

        // Registrar el rondero
        $ronderoData = $request->only([
            'fecha_inicio',
            'fecha_cese',
            'region_id',
            'provincia_id',
            'distrito_id',
            'sector_zona_id',
            'base_id'
        ]);

        // Asegurar que los IDs sean enteros (manejar null correctamente)
        $ronderoData['region_id'] = isset($ronderoData['region_id']) && !empty($ronderoData['region_id']) && $ronderoData['region_id'] != 0 ? (int)$ronderoData['region_id'] : null;
        $ronderoData['provincia_id'] = isset($ronderoData['provincia_id']) && !empty($ronderoData['provincia_id']) && $ronderoData['provincia_id'] != 0 ? (int)$ronderoData['provincia_id'] : null;
        $ronderoData['distrito_id'] = isset($ronderoData['distrito_id']) && !empty($ronderoData['distrito_id']) && $ronderoData['distrito_id'] != 0 ? (int)$ronderoData['distrito_id'] : null;
        $ronderoData['sector_zona_id'] = isset($ronderoData['sector_zona_id']) && !empty($ronderoData['sector_zona_id']) && $ronderoData['sector_zona_id'] != 0 ? (int)$ronderoData['sector_zona_id'] : null;
        $ronderoData['base_id'] = isset($ronderoData['base_id']) && !empty($ronderoData['base_id']) && $ronderoData['base_id'] != 0 ? (int)$ronderoData['base_id'] : null;

        $ronderoData['codigo_qr'] = (string)Str::uuid();
        $ronderoData['persona_id'] = $persona->id;
        $ronderoData['estado'] = true;
        $ronderoData['codigo_rondero'] = $codigo_rondero;

        // Verificar que todos los campos requeridos estén presentes
        if (empty($ronderoData['fecha_inicio'])) {
            throw new \Exception('La fecha de inicio es requerida');
        }

        $rondero = Rondero::create($ronderoData);

        // Crear usuario para el rondero con rol "Rondero"
        $usuario = $this->crearUsuarioParaRondero($persona, 'Rondero');

        // Finalizamos la transacción
        DB::commit();

        $tempPassword = $persona->docIdentidad . substr(str_replace(' ', '', $persona->nombres), 0, 2);

        return response()->json([
            'state' => 'success',
            'message' => 'Rondero registrado correctamente',
            'data' => [
                'rondero_id' => $rondero->id,
                'persona_id' => $persona->id,
                'codigo_rondero' => $codigo_rondero,
                'usuario' => $persona->docIdentidad,
                'password_temporal' => $tempPassword
            ]
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Error al crear rondero: ' . $e->getMessage());
        Log::error($e->getTraceAsString());

        return response()->json([
            'state' => 'error',
            'message' => 'Error al registrar el rondero: ' . $e->getMessage(),
            'details' => env('APP_DEBUG') ? $e->getTraceAsString() : null
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

/**
 * Generar código único de rondero
 * Formato: Año (2 dígitos) + Número secuencial (6 dígitos)
 * Ejemplo: 26000001 (2026-000001)
 */
private function generarCodigoRondero()
{
    $year = date('y'); // 2 dígitos del año (26 para 2026)

    // Obtener el último código generado para el año actual
    $ultimoRondero = Rondero::where('codigo_rondero', 'like', $year . '%')
        ->orderBy('codigo_rondero', 'desc')
        ->first();

    if ($ultimoRondero && $ultimoRondero->codigo_rondero) {
        // Extraer el número secuencial (últimos 6 dígitos)
        $ultimoNumero = (int)substr($ultimoRondero->codigo_rondero, 2);
        $nuevoNumero = $ultimoNumero + 1;
    } else {
        $nuevoNumero = 1;
    }

    // Formatear con ceros a la izquierda (6 dígitos)
    $codigo = $year . str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);

    return $codigo;
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $rondero = Rondero::with('persona')->findOrFail($id);
            return new RonderoResource($rondero);
        } catch (\Exception $e) {
            Log::error('Error al mostrar rondero: ' . $e->getMessage());
            return response()->json([
                'state' => 'error',
                'message' => 'Rondero no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }


    public function update(RonderoRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            // Actualizar RONDERO
            $rondero = Rondero::query()->findOrFail($id);
            $rondero->fecha_inicio = $request->input('fecha_inicio');
            $rondero->fecha_cese = $request->input('fecha_cese');
            $rondero->estado = $request->input('estado', $rondero->estado);
            $rondero->region_id = $request->input('region_id');
            $rondero->provincia_id = $request->input('provincia_id');
            $rondero->distrito_id = $request->input('distrito_id');
            $rondero->sector_zona_id = $request->input('sector_zona_id');
            $rondero->base_id = $request->input('base_id');

            // 🔥 CORRECCIÓN: Usar codigo_rondero en lugar de partida_registral
            if ($request->has('codigo_rondero')) {
                $rondero->codigo_rondero = $request->input('codigo_rondero');
            }

            $rondero->save();

            // Actualizar PERSONA
            $persona = Persona::query()->findOrFail($rondero->persona_id);
            $persona->fecha_nacimiento = $request->input('persona.fecha_nacimiento');
            $persona->genero = $request->input('persona.genero');
            $persona->direccion = $request->input('persona.direccion');
            $persona->celular = $request->input('persona.celular');
            $persona->email = $request->input('persona.email');

            // Manejo de foto - guardar base64 nuevo o normalizar URL/nombre existente
            if ($request->has('persona.foto')) {
                $fotoAnterior = $this->normalizarNombreFoto($persona->foto ?? '');
                $fotoActualizada = $this->persistirFotoDesdeEntrada($request->input('persona.foto'), $fotoAnterior);

                if (!empty($fotoActualizada)) {
                    if (!empty($fotoAnterior) && $fotoAnterior !== $fotoActualizada) {
                        $this->eliminarFotoAnteriorSiExiste($fotoAnterior);
                    }

                    $persona->foto = $fotoActualizada;
                } else {
                    $persona->foto = $fotoAnterior;
                }
            }

            $persona->save();

            DB::commit();

            return response()->json([
                "state" => "success",
                "message" => "El registro se actualizó correctamente."
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar rondero: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'state' => 'error',
                'message' => 'Error al actualizar el rondero: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Método auxiliar para eliminar foto anterior si existe
     */
    private function eliminarFotoAnteriorSiExiste($nombreArchivo)
    {
        $nombreArchivo = $this->normalizarNombreFoto($nombreArchivo);

        if (!empty($nombreArchivo)) {
            try {
                $rutaRelativa = 'fotos_personas/' . $nombreArchivo;
                if (Storage::disk('files_rondas')->exists($rutaRelativa)) {
                    Storage::disk('files_rondas')->delete($rutaRelativa);
                    Log::info("Foto anterior eliminada: {$nombreArchivo}");
                    return true;
                }
            } catch (\Exception $e) {
                Log::warning("Error al eliminar foto anterior {$nombreArchivo}: " . $e->getMessage());
            }
        }
        return false;
    }

    private function normalizarNombreFoto(?string $foto): ?string
    {
        if (empty($foto)) {
            return null;
        }

        $foto = trim($foto);

        if (str_starts_with($foto, 'data:image')) {
            return null;
        }

        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            $path = parse_url($foto, PHP_URL_PATH) ?: '';
            $basename = basename($path);
            return $basename !== '.' && $basename !== '/' ? $basename : null;
        }

        if (str_contains($foto, '/')) {
            $basename = basename(parse_url($foto, PHP_URL_PATH) ?: $foto);
            return $basename !== '.' && $basename !== '/' ? $basename : null;
        }

        return $foto;
    }

    private function obtenerImagenDesdeEntrada(?string $foto): ?array
    {
        if (empty($foto)) {
            return null;
        }

        $mime = 'image/jpeg';
        $payload = trim($foto);

        if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,/', $payload, $matches)) {
            $mime = $matches[1];
            $payload = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $payload);
        }

        $binary = base64_decode($payload, true);

        if ($binary === false || strlen($binary) < 100) {
            return null;
        }

        $imageInfo = @getimagesizefromstring($binary);
        if ($imageInfo === false || empty($imageInfo['mime'])) {
            return null;
        }

        return [
            'binary' => $binary,
            'mime' => $imageInfo['mime'] ?? $mime,
        ];
    }

    private function persistirFotoDesdeEntrada(?string $foto, ?string $fotoActual = null): ?string
    {
        $imagen = $this->obtenerImagenDesdeEntrada($foto);

        if ($imagen !== null) {
            try {
                $extension = match ($imagen['mime']) {
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };

                $imageName = 'imagen_' . time() . '_' . Str::random(10) . '.' . $extension;
                $directory = 'fotos_personas';

                if (!Storage::disk('files_rondas')->exists($directory)) {
                    Storage::disk('files_rondas')->makeDirectory($directory);
                }

                Storage::disk('files_rondas')->put($directory . '/' . $imageName, $imagen['binary']);

                return $imageName;
            } catch (\Exception $e) {
                Log::warning('Error al procesar foto del rondero: ' . $e->getMessage());
                return $fotoActual;
            }
        }

        return $this->normalizarNombreFoto($foto) ?: $fotoActual;
    }

    private function recuperarFotoDesdeReniec(Persona $persona): ?string
    {
        try {
            if (empty($persona->docIdentidad)) {
                return null;
            }

            /** @var ReniecService $reniecService */
            $reniecService = app(ReniecService::class);
            $datosReniec = $reniecService->consultarDNI($persona->docIdentidad);
            $fotoBase64 = $datosReniec['foto_base64'] ?? null;

            if (empty($fotoBase64) || !is_string($fotoBase64)) {
                return null;
            }

            $fotoRecuperada = $this->persistirFotoDesdeEntrada($fotoBase64, $this->normalizarNombreFoto($persona->foto ?? ''));

            if (!empty($fotoRecuperada)) {
                $persona->foto = $fotoRecuperada;
                $persona->save();
                Log::info('Foto recuperada desde RENIEC para persona DNI: ' . $persona->docIdentidad);
                return $fotoRecuperada;
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo recuperar foto desde RENIEC para DNI ' . ($persona->docIdentidad ?? 'N/A') . ': ' . $e->getMessage());
        }

        return null;
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!auth()->check() || !auth()->user()->hasRole('SuperAdministrador')) {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Solo el SuperAdministrador puede eliminar ronderos',
            ];
            return response()->json(['error' => $msg], Response::HTTP_FORBIDDEN);
        }

        try {
            $rondero = Rondero::query()->findOrFail($id);
            $rondero->eliminado = true;
            $rondero->save();

            $response = [
                "state" => "success",
                "message" => "Registro eliminado",
            ];
            return response()->json($response, Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error al eliminar rondero: ' . $e->getMessage());
            return response()->json([
                'state' => 'error',
                'message' => 'Error al eliminar el rondero'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.rondero.eliminar', 'api')) {
            try {
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
            } catch (\Exception $e) {
                return response()->json(['error' => "Error interno del servidor"], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            try {
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
            } catch (\Exception $e) {
                return response()->json(['error' => "Error interno del servidor"], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $tempFiles = [];
        $id = $request->input('id');
        $url_front = $request->input('url_front');
        $appUrl = rtrim(config('app.url'), '/');

        if (empty($url_front)) {
            $origin = $request->headers->get('origin');
            $referer = $request->headers->get('referer');

            if (!empty($origin)) {
                $url_front = rtrim($origin, '/');
            } elseif (!empty($referer)) {
                $parsedReferer = parse_url($referer);
                if (!empty($parsedReferer['scheme']) && !empty($parsedReferer['host'])) {
                    $url_front = $parsedReferer['scheme'] . '://' . $parsedReferer['host'] . (!empty($parsedReferer['port']) ? ':' . $parsedReferer['port'] : '');
                }
            }
        }

        if (empty($url_front)) {
            $url_front = $appUrl;
        }

        $url_front = rtrim($url_front, '/');

        if($id != null) {
            try {
                $rondero = Rondero::with(['persona', 'region', 'provincia', 'distrito', 'sector_zona', 'base', 'comites.cargo'])->findOrFail($id);

                // DEBUG: Información inicial
                Log::info("=== GENERANDO CARNET PARA RONDERO ID: {$id} ===");
                Log::info("Nombre: " . ($rondero->persona->nombres ?? 'N/A'));
                Log::info("Foto en BD: '" . ($rondero->persona->foto ?? 'VACÍA') . "'");

                // Texto QR
                $texto_qr = $url_front . '/validar-qr?qr=' . $rondero->codigo_qr;
                Log::info('URL usada para QR: ' . $texto_qr);

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

                $result = $writer->write($qrCode);

                // Guardar QR dentro del proyecto para que DomPDF pueda leerlo respetando el chroot
                $tempDirectory = storage_path('app/carnets_temp');
                if (!is_dir($tempDirectory)) {
                    mkdir($tempDirectory, 0755, true);
                }

                $qrTempPath = $tempDirectory . DIRECTORY_SEPARATOR . 'qr_carnet_' . $rondero->id . '_' . time() . '.png';
                file_put_contents($qrTempPath, $result->getString());
                $data['qrCodePath'] = 'file://' . str_replace('\\', '/', $qrTempPath);
                $tempFiles[] = $qrTempPath;

                // ====== SOLUCIÓN DEFINITIVA PARA LA FOTO ======
                $foto_final = '';
                $foto_mime = 'image/jpeg'; // MIME por defecto
                $foto_path = '';

                if (!empty($rondero->persona->foto)) {
                    try {
                        $nombreArchivoOriginal = $rondero->persona->foto;
                        $nombreArchivo = $this->normalizarNombreFoto($nombreArchivoOriginal);

                        if (!empty($nombreArchivo) && $nombreArchivo !== $nombreArchivoOriginal) {
                            $rondero->persona->foto = $nombreArchivo;
                            $rondero->persona->save();
                            Log::info('Foto normalizada en BD: ' . $nombreArchivo);
                        }

                        if (empty($nombreArchivo)) {
                            throw new \Exception('El valor almacenado en persona.foto no es válido');
                        }

                        $rutaRelativa = 'fotos_personas/' . $nombreArchivo;

                        // DEBUG: Verificar disco configurado
                        Log::info("Buscando foto: " . $rutaRelativa);
                        Log::info("Ruta disco raíz: " . Storage::disk('files_rondas')->path(''));

                        // 1. PRIMERO: Buscar en el disco configurado 'files_rondas'
                        if (Storage::disk('files_rondas')->exists($rutaRelativa)) {
                            $imageData = Storage::disk('files_rondas')->get($rutaRelativa);
                            $foto_final = base64_encode($imageData);
                            $realPath = Storage::disk('files_rondas')->path($rutaRelativa);
                            if (file_exists($realPath)) {
                                $foto_path = 'file://' . str_replace('\\', '/', $realPath);
                            }

                            // Detectar MIME type real del binario
                            if (function_exists('finfo_buffer')) {
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $detectedMime = finfo_buffer($finfo, $imageData);
                                finfo_close($finfo);
                                if ($detectedMime && str_starts_with($detectedMime, 'image/')) {
                                    $foto_mime = $detectedMime;
                                }
                            }

                            Log::info("✓ Foto encontrada en disco 'files_rondas'");
                            Log::info("✓ Tamaño imagen: " . strlen($imageData) . " bytes");
                            Log::info("✓ MIME detectado: " . $foto_mime);
                            Log::info("✓ Ruta completa: " . Storage::disk('files_rondas')->path($rutaRelativa));

                        } else {
                            Log::warning("✗ Foto NO encontrada en disco 'files_rondas'");

                            $fotoRecuperada = $this->recuperarFotoDesdeReniec($rondero->persona);
                            if (!empty($fotoRecuperada)) {
                                $nombreArchivo = $fotoRecuperada;
                                $rutaRelativa = 'fotos_personas/' . $nombreArchivo;

                                if (Storage::disk('files_rondas')->exists($rutaRelativa)) {
                                    $imageData = Storage::disk('files_rondas')->get($rutaRelativa);
                                    $foto_final = base64_encode($imageData);
                                    $realPath = Storage::disk('files_rondas')->path($rutaRelativa);
                                    if (file_exists($realPath)) {
                                        $foto_path = 'file://' . str_replace('\\', '/', $realPath);
                                    }

                                    if (function_exists('finfo_buffer')) {
                                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                        $detectedMime = finfo_buffer($finfo, $imageData);
                                        finfo_close($finfo);
                                        if ($detectedMime && str_starts_with($detectedMime, 'image/')) {
                                            $foto_mime = $detectedMime;
                                        }
                                    }

                                    Log::info("✓ Foto recuperada desde RENIEC y encontrada en disco local");
                                }
                            }

                            // 2. SEGUNDO: Buscar en rutas alternativas comunes
                            $rutasAlternativas = array_filter(array_unique([
                                // Ruta del disco files_rondas resuelta por configuración
                                config('filesystems.disks.files_rondas.root') . DIRECTORY_SEPARATOR . 'fotos_personas' . DIRECTORY_SEPARATOR . $nombreArchivo,
                                // Rutas comunes de Laravel en desarrollo/producción
                                public_path('files_rondas/fotos_personas/' . $nombreArchivo),
                                public_path('user_avatars/' . $nombreArchivo),
                                storage_path('app/files_rondas/fotos_personas/' . $nombreArchivo),
                                storage_path('files_rondas/fotos_personas/' . $nombreArchivo),
                                storage_path('app/public/fotos_personas/' . $nombreArchivo),
                                public_path($nombreArchivo),
                            ]));

                            foreach ($rutasAlternativas as $ruta) {
                                Log::info("Buscando en ruta alternativa: " . $ruta);
                                if (file_exists($ruta)) {
                                    $imageData = file_get_contents($ruta);
                                    $foto_final = base64_encode($imageData);
                                    $foto_path = 'file://' . str_replace('\\', '/', $ruta);
                                    // Detectar MIME type real
                                    if (function_exists('finfo_buffer')) {
                                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                        $detectedMime = finfo_buffer($finfo, $imageData);
                                        finfo_close($finfo);
                                        if ($detectedMime && str_starts_with($detectedMime, 'image/')) {
                                            $foto_mime = $detectedMime;
                                        }
                                    }
                                    Log::info("✓ Foto encontrada en ruta alternativa");
                                    break;
                                }
                            }
                        }

                        // 3. Si aún no se encontró, verificar si el nombre ya es base64
                        if (empty($foto_final)) {
                            $base64Limpio = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $nombreArchivoOriginal);
                            $imageData = base64_decode($base64Limpio, true);

                            if ($imageData !== false && strlen($imageData) > 100) {
                                $foto_final = base64_encode($imageData);

                                if (function_exists('finfo_buffer')) {
                                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                    $detectedMime = finfo_buffer($finfo, $imageData);
                                    finfo_close($finfo);
                                    if ($detectedMime && str_starts_with($detectedMime, 'image/')) {
                                        $foto_mime = $detectedMime;
                                    }
                                }

                                $extension = match ($foto_mime) {
                                    'image/png' => 'png',
                                    'image/gif' => 'gif',
                                    'image/webp' => 'webp',
                                    default => 'jpg',
                                };

                                $fotoTempPath = $tempDirectory . DIRECTORY_SEPARATOR . 'foto_carnet_' . $rondero->id . '_' . time() . '.' . $extension;
                                file_put_contents($fotoTempPath, $imageData);
                                $foto_path = 'file://' . str_replace('\\', '/', $fotoTempPath);
                                $tempFiles[] = $fotoTempPath;
                                Log::info("✓ La foto fue reconstruida desde base64");
                            }
                        }

                        // 4. Si después de todo no se encontró
                        if (empty($foto_final)) {
                            $foto_final = $this->getDefaultUserImageBase64();

                            if (!empty($foto_final)) {
                                $base64Default = base64_decode($foto_final, true);
                                if ($base64Default !== false) {
                                    $defaultImagePath = public_path('images/default-user.jpg');
                                    if (file_exists($defaultImagePath)) {
                                        $foto_path = 'file://' . str_replace('\\', '/', $defaultImagePath);
                                        $foto_mime = 'image/jpeg';
                                    } else {
                                        $defaultTempPath = $tempDirectory . DIRECTORY_SEPARATOR . 'foto_default_' . $rondero->id . '_' . time() . '.jpg';
                                        file_put_contents($defaultTempPath, $base64Default);
                                        $foto_path = 'file://' . str_replace('\\', '/', $defaultTempPath);
                                        $foto_mime = 'image/jpeg';
                                        $tempFiles[] = $defaultTempPath;
                                    }
                                }
                            }

                            Log::warning("✗ Foto no encontrada en ninguna ubicación, se usará imagen por defecto");
                        }

                    } catch (\Exception $e) {
                        Log::error('Error al cargar la foto: ' . $e->getMessage());
                        Log::error($e->getTraceAsString());
                        $foto_final = '';
                    }
                } else {
                    Log::info("No hay foto registrada en la BD");
                }

                $data['foto'] = $foto_final; // Base64 PURO o vacío
                $data['foto_mime'] = $foto_mime; // MIME type detectado
                $data['fotoPath'] = $foto_path;
                Log::info("Foto enviada a vista (longitud base64): " . strlen($foto_final));
                // ====== FIN SOLUCIÓN FOTO ======

                // Datos personales
                $data['apellido_paterno'] = $rondero->persona->apellido_paterno ?? '';
                $data['apellido_materno'] = $rondero->persona->apellido_materno ?? '';
                $data['nombres'] = $rondero->persona->nombres ?? '';
                $data['numero'] = $rondero->persona->docIdentidad ?? '';

                // Cargos
                $data['cargos'] = [];
                if ($rondero->comites) {
                    foreach ($rondero->comites as $comite) {
                        if ($comite->cargo) {
                            $data['cargos'][] = $comite->cargo->descripcion;
                        }
                    }
                }

                // Ubicación

                try {
                    // Verificar que las relaciones estén cargadas
                    if ($rondero->region) {
                        $data['region'] = $rondero->region->descripcion ?? 'N/A';
                        Log::info("Región cargada: " . $data['region']);
                    } else {
                        $data['region'] = 'N/A';
                        Log::warning("Región NO cargada para rondero ID: " . $id);
                    }

                    if ($rondero->provincia) {
                        $data['provincia'] = $rondero->provincia->descripcion ?? 'N/A';
                        Log::info("Provincia cargada: " . $data['provincia']);
                    } else {
                        $data['provincia'] = 'N/A';
                        Log::warning("Provincia NO cargada para rondero ID: " . $id);
                    }

                    if ($rondero->distrito) {
                        $data['distrito'] = $rondero->distrito->descripcion ?? 'N/A';
                        Log::info("Distrito cargada: " . $data['distrito']);
                    } else {
                        $data['distrito'] = 'N/A';
                        Log::warning("Distrito NO cargada para rondero ID: " . $id);
                    }

                    if ($rondero->sector_zona) {
                        $data['sector_zona'] = $rondero->sector_zona->descripcion ?? 'N/A';
                        Log::info("Sector cargado: " . $data['sector_zona']);
                    } else {
                        $data['sector_zona'] = 'N/A';
                        Log::warning("Sector NO cargado para rondero ID: " . $id);
                    }

                    if ($rondero->base) {
                        $data['base'] = $rondero->base->nombre_descripcion ?? 'N/A';
                        Log::info("Base cargada: " . $data['base']);
                    } else {
                        $data['base'] = 'N/A';
                        Log::warning("Base NO cargada para rondero ID: " . $id);
                    }

                } catch (\Exception $e) {
                    Log::error('Error al cargar ubicación: ' . $e->getMessage());
                    $data['region'] = 'N/A';
                    $data['provincia'] = 'N/A';
                    $data['distrito'] = 'N/A';
                    $data['sector_zona'] = 'N/A';
                    $data['base'] = 'N/A';
                }

                // Fechas formateadas
                $data['fecha_inicio'] = $rondero->fecha_inicio
                    ? \Carbon\Carbon::parse($rondero->fecha_inicio)->format('d/m/Y')
                    : '';
                $data['fecha_cese'] = $rondero->fecha_cese
                    ? \Carbon\Carbon::parse($rondero->fecha_cese)->format('d/m/Y')
                    : '';

                // ID del carnet
                $data['carnet_id'] = str_pad($rondero->id, 6, '0', STR_PAD_LEFT);

                // DEBUG: Resumen final
                Log::info("=== RESUMEN DATOS ENVIADOS A VISTA ===");
                Log::info("Foto (base64 length): " . strlen($data['foto']));
                Log::info("Foto MIME: " . $data['foto_mime']);
                Log::info("Nombres: " . $data['nombres']);
                Log::info("DNI: " . $data['numero']);
                Log::info("=====================================");

            } catch (\Exception $e) {
                Log::error('Error al generar carnet: ' . $e->getMessage());
                Log::error($e->getTraceAsString());
                return response()->json([
                    'error' => 'Error al generar el carnet: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }

        // Generar PDF
        try {
            Log::info("Generando PDF del carnet...");

            // Cargar la vista con los datos
            $pdf = PDF::loadView('carnet', $data);

            // Configurar el PDF para carnet (tamaño personalizado)
            $pdf->setPaper([0, 0, 8.44, 13.15], 'portrait'); // Tamaño carnet en cm (8.44cm x 13.15cm)

            Log::info("✓ PDF generado exitosamente");

            // Nombre del archivo
            $nombreArchivo = 'carnet_' .
                            ($data['apellido_paterno'] ?? '') . '_' .
                            ($data['nombres'] ?? '') . '_' .
                            date('Ymd_His') . '.pdf';

            return $pdf->download($nombreArchivo);

        } catch (\Exception $e) {
            Log::error('Error al generar PDF: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Error al generar el PDF del carnet: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } finally {
            foreach ($tempFiles as $tempFile) {
                if (!empty($tempFile) && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

/**
 * Método para probar la foto de un rondero específico
 */
public function testFotoRondero($id)
{
    try {
        $rondero = Rondero::with('persona')->findOrFail($id);

        $resultado = [
            'rondero_id' => $rondero->id,
            'persona_id' => $rondero->persona_id,
            'nombre_completo' => $rondero->persona->nombres . ' ' . $rondero->persona->apellido_paterno,
            'dni' => $rondero->persona->docIdentidad,
            'foto_en_bd' => $rondero->persona->foto,
            'disco_configurado' => config('filesystems.disks.files_rondas'),
        ];

        if (!empty($rondero->persona->foto)) {
            $nombreArchivo = $this->normalizarNombreFoto($rondero->persona->foto);
            $rutaRelativa = 'fotos_personas/' . $nombreArchivo;

            // Verificar en disco files_rondas
            $resultado['existe_en_disco'] = Storage::disk('files_rondas')->exists($rutaRelativa);
            $resultado['ruta_completa_disco'] = Storage::disk('files_rondas')->path($rutaRelativa);

            if ($resultado['existe_en_disco']) {
                $imageData = Storage::disk('files_rondas')->get($rutaRelativa);
                $resultado['tamano_imagen'] = strlen($imageData);
                $resultado['base64_preview'] = substr(base64_encode($imageData), 0, 100) . '...';

                // Mostrar la imagen directamente en la respuesta HTML
                echo "<h2>Rondero: " . $resultado['nombre_completo'] . "</h2>";
                echo "<h3>DNI: " . $resultado['dni'] . "</h3>";
                echo "<p>Foto en BD: <strong>" . $resultado['foto_en_bd'] . "</strong></p>";
                echo "<p>Existe en disco: <strong style='color:green'>✓ SÍ</strong></p>";
                echo "<p>Ruta: <code>" . $resultado['ruta_completa_disco'] . "</code></p>";
                echo "<p>Tamaño: " . $resultado['tamano_imagen'] . " bytes</p>";
                echo "<br><img src='data:image/jpeg;base64," . base64_encode($imageData) . "' style='max-width:300px; border:2px solid #ccc;'>";
                exit;
            } else {
                echo "<h2>Rondero: " . $resultado['nombre_completo'] . "</h2>";
                echo "<p style='color:red'>✗ Foto NO encontrada en disco 'files_rondas'</p>";
                echo "<p>Ruta buscada: <code>" . $resultado['ruta_completa_disco'] . "</code></p>";

                // Mostrar archivos disponibles
                echo "<h3>Archivos disponibles en el disco:</h3>";
                $archivos = Storage::disk('files_rondas')->allFiles('fotos_personas');
                foreach ($archivos as $archivo) {
                    echo "- " . $archivo . "<br>";
                }
                exit;
            }
        } else {
            echo "<h2>Rondero: " . $resultado['nombre_completo'] . "</h2>";
            echo "<p style='color:orange'>⚠ No hay foto registrada en la base de datos</p>";
            exit;
        }

    } catch (\Exception $e) {
        echo "<h2 style='color:red'>Error:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        exit;
    }
}

    /**
     * Método auxiliar para obtener una imagen por defecto en base64 (SIN PREFIJO)
     */
    private function getDefaultUserImageBase64()
    {
        // Primero intentar con una imagen por defecto real si existe
        $defaultImagePath = public_path('images/default-user.jpg');
        if (file_exists($defaultImagePath)) {
            $imageData = file_get_contents($defaultImagePath);
            return base64_encode($imageData);
        }

        // Si no existe, crear una imagen SVG simple
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#f0f0f0"/>
            <circle cx="100" cy="80" r="50" fill="#cccccc"/>
            <circle cx="100" cy="200" r="80" fill="#cccccc"/>
            <circle cx="75" cy="75" r="8" fill="#666666"/>
            <circle cx="125" cy="75" r="8" fill="#666666"/>
            <path d="M80,100 Q100,130 120,100" stroke="#666666" stroke-width="3" fill="none"/>
        </svg>';

        return base64_encode($svg); // Base64 PURO
    }
    public function getFoto($id)
    {
        try {
            $rondero = Rondero::with('persona')->findOrFail($id);

            if (empty($rondero->persona->foto)) {
                return response()->json(['error' => 'No hay foto'], 404);
            }

            $nombreArchivo = $this->normalizarNombreFoto($rondero->persona->foto);

            if (empty($nombreArchivo)) {
                return response()->json(['error' => 'Foto no válida'], 404);
            }

            $rutaRelativa = 'fotos_personas/' . $nombreArchivo;

            if (!Storage::disk('files_rondas')->exists($rutaRelativa)) {
                return response()->json(['error' => 'Foto no encontrada'], 404);
            }

            $file = Storage::disk('files_rondas')->get($rutaRelativa);
            $mimeType = Storage::disk('files_rondas')->mimeType($rutaRelativa);

            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');

        } catch (\Exception $e) {
            Log::error('Error al obtener foto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar la foto'], 500);
        }
    }

    /**
     * Método de prueba para verificar fotos (puedes llamarlo desde una ruta temporal)
     */
    public function testFoto($id)
    {
        try {
            $rondero = Rondero::with('persona')->findOrFail($id);

            $response = [
                'rondero_id' => $rondero->id,
                'persona_id' => $rondero->persona_id,
                'nombre' => $rondero->persona->nombres . ' ' . $rondero->persona->apellido_paterno,
                'foto_en_bd' => $rondero->persona->foto,
                'existe_archivo' => false,
                'ruta_buscada' => '',
                'base64_length' => 0
            ];

            if (!empty($rondero->persona->foto)) {
                $nombreArchivo = $this->normalizarNombreFoto($rondero->persona->foto);
                $ruta = public_path('files_rondas/fotos_personas/' . $nombreArchivo);
                $response['ruta_buscada'] = $ruta;
                $response['existe_archivo'] = file_exists($ruta);

                if (file_exists($ruta)) {
                    $imageData = file_get_contents($ruta);
                    $base64 = base64_encode($imageData);
                    $response['base64_length'] = strlen($base64);
                    $response['base64_preview'] = substr($base64, 0, 100) . '...';
                }
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getPotencialesAdministradores(Request $request)
    {
        try {
            // Buscar ronderos que tengan un cargo de Presidente
            $ronderos = Rondero::with(['persona', 'comites.cargo'])
                ->where('eliminado', false)
                ->where('estado', true)
                ->whereHas('comites', function($query) {
                    $query->whereHas('cargo', function($q) {
                        $q->where('descripcion', 'ILIKE', '%PRESIDENTE%');
                    });
                })
                ->get();

            return RonderoResource::collection($ronderos);
        } catch (Exception $e) {
            Log::error('Error en getPotencialesAdministradores: ' . $e->getMessage());
            return response()->json([
                'state' => 'error',
                'message' => 'Error al obtener ronderos'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Crear un usuario para el rondero con el rol especificado
     */
    private function crearUsuarioParaRondero($persona, $rolNombre = 'Rondero')
    {
        try {
            // Verificar si ya existe un usuario para esta persona
            $usuarioExistente = User::where('persona_id', $persona->id)->first();

            if ($usuarioExistente) {
                Log::info("Ya existe un usuario para la persona ID: {$persona->id}");
                return $usuarioExistente;
            }

            // Generar contraseña temporal (DNI + primeras dos letras del nombre)
            $tempPassword = $persona->docIdentidad . substr(str_replace(' ', '', $persona->nombres), 0, 2);

            // Crear usuario
            $usuario = new User();
            $usuario->name = $persona->docIdentidad; // Usuario = DNI
            $usuario->email = $persona->email ?? $persona->docIdentidad . '@rondero.local';
            $usuario->password = Hash::make($tempPassword);
            $usuario->persona_id = $persona->id;
            $usuario->cambioPassword = true; // Forzar cambio de contraseña en el primer inicio
            $usuario->save();

            // Buscar o crear el rol
            $rol = Role::where('name', $rolNombre)->first();

            if (!$rol) {
                // Crear el rol si no existe
                $rol = Role::create([
                    'name' => $rolNombre,
                    'guard_name' => 'api',
                    'created_by' => auth()->user()->id ?? 1
                ]);

                // Asignar permisos básicos según el rol
                if ($rolNombre === 'Rondero') {
                    $this->asignarPermisosRondero($rol);
                } elseif ($rolNombre === 'Administrador') {
                    $this->asignarPermisosAdministrador($rol);
                }
            }

            $usuario->assignRole($rol);

            Log::info("Usuario creado para rondero:", [
                'persona_id' => $persona->id,
                'usuario' => $usuario->name,
                'password_temporal' => $tempPassword,
                'rol' => $rolNombre
            ]);

            return $usuario;

        } catch (\Exception $e) {
            Log::error('Error al crear usuario para rondero: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Asignar permisos para el rol Rondero
     */
    private function asignarPermisosRondero($rol)
    {
        $permissions = [
            'pub.rondero.listar',
            'pub.rondero.ver',
            'pub.rondero.actualizar',
            'pub.rondero.carnet',
        ];

        foreach ($permissions as $permName) {
            $permission = Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'api'
            ]);
            $rol->givePermissionTo($permission);
        }
    }

    /**
     * Asignar permisos para el rol Administrador
     */
    private function asignarPermisosAdministrador($rol)
    {
        $permissions = [
            'pub.rondero.listar',
            'pub.rondero.ver',
            'pub.rondero.crear',
            'pub.rondero.actualizar',
            'pub.rondero.eliminar',
            'pub.rondero.carnet',
            'pub.bases.listar',
            'pub.bases.ver',
            'pub.bases.crear',
            'pub.bases.actualizar',
            'pub.bases.eliminar',
        ];

        foreach ($permissions as $permName) {
            $permission = Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'api'
            ]);
            $rol->givePermissionTo($permission);
        }
    }
    /**
     * Buscar rondero por DNI
     */
    public function buscarPorDNI(Request $request)
    {
        try {
            $dni = $request->input('dni');

            if (empty($dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI requerido'
                ], Response::HTTP_BAD_REQUEST);
            }

            // 🔥 IMPORTANTE: Usar el mismo servicio que usa VerificarIdentidad
            $reniecService = new \App\Services\ReniecService();
            $datosReniec = $reniecService->consultarDNI($dni);

            if (!$datosReniec) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener datos del DNI'
                ], Response::HTTP_NOT_FOUND);
            }

            // Buscar si ya existe como rondero
            $persona = Persona::where('docIdentidad', $dni)->first();
            $es_rondero = false;
            $rondero = null;

            if ($persona) {
                $rondero = Rondero::where('persona_id', $persona->id)
                    ->where('eliminado', false)
                    ->first();
                $es_rondero = !is_null($rondero);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'dni' => $dni,
                    'nombres' => $datosReniec['nombres'] ?? '',
                    'apellido_paterno' => $datosReniec['apellido_paterno'] ?? '',
                    'apellido_materno' => $datosReniec['apellido_materno'] ?? '',
                    'nombre_completo' => $datosReniec['nombre_completo'] ?? '',
                    'direccion' => $datosReniec['direccion'] ?? '',
                    'genero' => $datosReniec['genero'] ?? '',
                ],
                'es_rondero' => $es_rondero,
                'rondero_data' => $rondero ? new RonderoResource($rondero) : null
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error en buscarPorDNI: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
