<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ReniecService;
use App\Models\Publico\Rondero;
use App\Models\Publico\Persona;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificacionController extends Controller
{
    protected $reniecService;

    public function __construct(ReniecService $reniecService)
    {
        $this->reniecService = $reniecService;
    }

    /**
     * Verificar identidad por DNI
     */
    public function verificarPorDNI($dni)
    {
        try {
            // Validar DNI
            if (!preg_match('/^[0-9]{8}$/', $dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI inválido. Debe tener 8 dígitos.'
                ], 400);
            }

            // Buscar en la base de datos local
            $persona = Persona::where('docIdentidad', $dni)->first();
            $rondero = null;
            
            if ($persona) {
                $rondero = Rondero::where('persona_id', $persona->id)->first();
            }
            
            // Consultar RENIEC
            $datosReniec = $this->reniecService->consultarDNI($dni);
            
            // 🔥 CORRECCIÓN: Usar el género de RENIEC si existe
            $genero = $datosReniec['genero'] ?? '';
            
            // Si RENIEC no devuelve género (lo cual es raro), calcular por DNI como fallback
            if (empty($genero)) {
                $ultimoDigito = substr($dni, -1);
                $genero = ($ultimoDigito % 2 == 0) ? 'FEMENINO' : 'MASCULINO';
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
                    'estado_civil' => $datosReniec['estado_civil'] ?? '',
                    'genero' => $genero,  // 🔥 Ahora usa el género real de RENIEC
                    'tiene_foto' => $datosReniec['tiene_foto'] ?? false,
                    'foto_base64' => $datosReniec['foto_base64'] ?? null,
                ],
                'es_rondero' => !is_null($rondero),
                'rondero_info' => $rondero ? [
                    'id' => $rondero->id,
                    'codigo_rondero' => $rondero->codigo_rondero,
                    'fecha_inicio' => $rondero->fecha_inicio,
                    'estado' => $rondero->estado
                ] : null,
                'fuente' => $datosReniec['fuente'] ?? 'reniec_rest'
            ]);

        } catch (Exception $e) {
            Log::error('Error en verificarPorDNI: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar RENIEC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar identidad comparando datos ingresados con RENIEC
     */
    public function verificarDatos(Request $request)
    {
        try {
            $dni = $request->input('dni');
            $nombres = $request->input('nombres');
            $apellido_paterno = $request->input('apellido_paterno');
            $apellido_materno = $request->input('apellido_materno');
            
            // Validar DNI
            if (!preg_match('/^[0-9]{8}$/', $dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI inválido. Debe tener 8 dígitos.'
                ], 400);
            }
            
            // Consultar RENIEC
            $datosReniec = $this->reniecService->consultarDNI($dni);
            
            // Normalizar datos para comparación
            $normalizar = function($texto) {
                if (empty($texto)) return '';
                $texto = mb_strtoupper(trim($texto), 'UTF-8');
                $texto = preg_replace('/\s+/', ' ', $texto);
                $texto = str_replace(
                    ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'],
                    ['A', 'E', 'I', 'O', 'U', 'N', 'U'],
                    $texto
                );
                return $texto;
            };
            
            $nombresReniec = $normalizar($datosReniec['nombres'] ?? '');
            $apellidoPaternoReniec = $normalizar($datosReniec['apellido_paterno'] ?? '');
            $apellidoMaternoReniec = $normalizar($datosReniec['apellido_materno'] ?? '');
            
            $nombresInput = $normalizar($nombres);
            $apellidoPaternoInput = $normalizar($apellido_paterno);
            $apellidoMaternoInput = $normalizar($apellido_materno);
            
            // Comparar
            $coinciden = (
                $nombresInput === $nombresReniec &&
                $apellidoPaternoInput === $apellidoPaternoReniec &&
                $apellidoMaternoInput === $apellidoMaternoReniec
            );
            
            // Calcular porcentaje de coincidencia
            $coincidencias = 0;
            $totalCampos = 3;
            
            if ($nombresInput === $nombresReniec) $coincidencias++;
            if ($apellidoPaternoInput === $apellidoPaternoReniec) $coincidencias++;
            if ($apellidoMaternoInput === $apellidoMaternoReniec) $coincidencias++;
            
            $porcentaje = round(($coincidencias / $totalCampos) * 100);
            
            // Buscar si es rondero
            $persona = Persona::where('docIdentidad', $dni)->first();
            $es_rondero = false;
            $rondero_info = null;
            
            if ($persona) {
                $rondero = Rondero::where('persona_id', $persona->id)->first();
                if ($rondero) {
                    $es_rondero = true;
                    $rondero_info = [
                        'id' => $rondero->id,
                        'codigo_rondero' => $rondero->codigo_rondero,
                        'estado' => $rondero->estado
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'coincide' => $coinciden,
                'porcentaje' => $porcentaje,
                'datos_reniec' => [
                    'nombres' => $datosReniec['nombres'] ?? '',
                    'apellido_paterno' => $datosReniec['apellido_paterno'] ?? '',
                    'apellido_materno' => $datosReniec['apellido_materno'] ?? '',
                    'nombre_completo' => $datosReniec['nombre_completo'] ?? '',
                    'direccion' => $datosReniec['direccion'] ?? '',
                ],
                'datos_ingresados' => [
                    'nombres' => $nombres,
                    'apellido_paterno' => $apellido_paterno,
                    'apellido_materno' => $apellido_materno,
                ],
                'es_rondero' => $es_rondero,
                'rondero_info' => $rondero_info,
                'fuente' => $datosReniec['fuente'] ?? 'reniec_rest'
            ]);

        } catch (Exception $e) {
            Log::error('Error en verificarDatos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar RENIEC: ' . $e->getMessage()
            ], 500);
        }
    }
}