<?php

namespace App\Http\Controllers;

use App\Models\Publico\Persona;
use App\Models\Publico\Rondero;
use App\Services\ReniecService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AuditoriaService;

class UtilsController extends Controller
{
    protected $reniecService;

    public function __construct(ReniecService $reniecService)
    {
        $this->reniecService = $reniecService;
    }

    /**
     * @throws Exception
     */
    public function getAOuthTokenDNI()
    {
        $tokenData = Cache::get('service_token');
        if ($tokenData && $tokenData['expires_at'] > now()) {
            return $tokenData['access_token'];
        }

        $response = Http::asForm()->post(env('RENIEC_GRC_AOUTH_URL_TOKEN'), [
            'grant_type' => env('RENIEC_GRC_GRANT_TYPE'),
            'client_id' => env('RENIEC_GRC_AOUTH_CLIENT_ID'),
            'client_secret' => env('RENIEC_GRC_AOUTH_CLIENT_SECRET'),
        ]);

        $data = $response->json();

        if (!isset($data['access_token'], $data['expires_in'])) {
            throw new \Exception('No se pudo obtener el token');
        }

        $expiresAt = now()->addSeconds($data['expires_in']);

        Cache::put('service_token', [
            'access_token' => $data['access_token'],
            'expires_at' => $expiresAt,
        ], $expiresAt);

        return $data['access_token'];
    }

    /**
     * Consulta datos de DNI usando el servicio REST oficial de RENIEC
     * Las credenciales se toman de la base de datos (tabla configuraciones)
     */
    public function getDataDNI($dni, $tipo_persona)
    {
        try {
            Log::info('🔍 Consulta RENIEC iniciada', ['dni' => $dni, 'tipo_persona' => $tipo_persona]);

            // Validar DNI
            if (!preg_match('/^[0-9]{8}$/', $dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI inválido. Debe tener 8 dígitos.'
                ], 400);
            }

            // Validar si es rondero y ya existe
            if ($tipo_persona == 'RONDERO') {
                if ($this->existsRonderoByDNI($dni)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe un rondero con el DNI ' . $dni
                    ], 400);
                }
            }

            // Buscar primero en base de datos local
            $persona_find = Persona::where('docIdentidad', $dni)->first();
            if ($persona_find) {
                $nombreCompleto = trim(($persona_find->apellido_paterno ?? '') . ' ' .
                    ($persona_find->apellido_materno ?? '') . ' ' .
                    ($persona_find->nombres ?? ''));

                Log::info('📝 Persona encontrada en BD local', ['dni' => $dni, 'nombre' => $nombreCompleto]);

                try {
                    $auditoria = AuditoriaService::registrarConsultaReniec($dni, ['nombre_completo' => $nombreCompleto], true);
                    Log::info('✅ Auditoría registrada en BD local', ['auditoria_id' => $auditoria ? $auditoria->id : 'NULL']);
                } catch (\Exception $e) {
                    Log::error('❌ Error al registrar auditoría: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'datos' => [
                        'nombres' => $persona_find->nombres ?? '',
                        'apellido_paterno' => $persona_find->apellido_paterno ?? '',
                        'apellido_materno' => $persona_find->apellido_materno ?? '',
                        'nombre_completo' => $nombreCompleto,
                        'fecha_nacimiento' => $persona_find->fecha_nacimiento ?? '',
                        'genero' => $persona_find->genero ?? '',
                        'celular' => $persona_find->celular ?? '',
                        'direccion' => $persona_find->direccion ?? '',
                        'email' => $persona_find->email ?? '',
                        'foto' => $persona_find->foto ?? ''
                    ],
                    'fuente' => 'base_datos_local'
                ]);
            }

            // 🔥 OBTENER CREDENCIALES DE LA BASE DE DATOS
            $restUrl = Configuracion::get('RENIEC_REST_URL');
            $dniUsuario = Configuracion::get('RENIEC_DNI_USUARIO');
            $password = Configuracion::get('RENIEC_PASSWORD');
            $rucUsuario = Configuracion::get('RENIEC_RUC_USUARIO');
            $timeout = (int) Configuracion::get('RENIEC_TIMEOUT', 60);

            // 🔥 LOGS PARA DEPURAR
            Log::info('🔍 Credenciales desde BD:', [
                'url' => $restUrl,
                'usuario' => $dniUsuario,
                'password' => $password ? '***' : 'vacío',
                'timeout' => $timeout
            ]);

            Log::info('📝 Usando credenciales de BD para RENIEC', [
                'url' => $restUrl,
                'usuario' => $dniUsuario,
                'timeout' => $timeout
            ]);

            // Consultar RENIEC usando credenciales de BD
            $datosReniec = $this->consultarReniecConCredenciales($dni, $restUrl, $dniUsuario, $password, $rucUsuario, $timeout);

            $nombreCompleto = trim(($datosReniec['nombres'] ?? '') . ' ' .
                ($datosReniec['apellido_paterno'] ?? '') . ' ' .
                ($datosReniec['apellido_materno'] ?? ''));

            Log::info('📝 Respuesta RENIEC', ['dni' => $dni, 'nombre' => $nombreCompleto]);

            $ultimoDigito = substr($dni, -1);
            $genero = ($ultimoDigito % 2 == 0) ? 'FEMENINO' : 'MASCULINO';

            try {
                $auditoria = AuditoriaService::registrarConsultaReniec($dni, [
                    'nombres' => $datosReniec['nombres'] ?? '',
                    'apellido_paterno' => $datosReniec['apellido_paterno'] ?? '',
                    'apellido_materno' => $datosReniec['apellido_materno'] ?? '',
                    'nombre_completo' => $nombreCompleto
                ], true);
                Log::info('✅ Auditoría registrada desde API', ['auditoria_id' => $auditoria ? $auditoria->id : 'NULL']);
            } catch (\Exception $e) {
                Log::error('❌ Error al registrar auditoría desde API: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'datos' => [
                    'nombres' => $datosReniec['nombres'] ?? '',
                    'apellido_paterno' => $datosReniec['apellido_paterno'] ?? '',
                    'apellido_materno' => $datosReniec['apellido_materno'] ?? '',
                    'nombre_completo' => $nombreCompleto,
                    'fecha_nacimiento' => '',
                    'genero' => $genero,
                    'direccion' => $datosReniec['direccion'] ?? '',
                ],
                'fuente' => $datosReniec['fuente'] ?? 'reniec_rest'
            ]);

        } catch (Exception $e) {
            Log::error('❌ Error en consulta RENIEC: ' . $e->getMessage());

            try {
                AuditoriaService::registrarConsultaReniec($dni, ['error' => $e->getMessage()], false);
            } catch (\Exception $ex) {
                Log::error('❌ Error al registrar auditoría de error: ' . $ex->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar RENIEC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar RENIEC usando credenciales de la base de datos
     */
    private function consultarReniecConCredenciales($dni, $url, $usuario, $password, $rucUsuario, $timeout)
    {
        try {
            $response = Http::timeout($timeout)
                ->withOptions(['verify' => false])
                ->post($url, [
                    'dni' => $dni,
                    'usuario' => $usuario,
                    'password' => $password,
                    'rucUsuario' => $rucUsuario
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Error en API RENIEC: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error consultando RENIEC REST: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Datos de prueba para desarrollo
     */
    private function datosDePrueba($dni)
    {
        $apellidosFicticios = ['GARCIA', 'RODRIGUEZ', 'LOPEZ', 'MARTINEZ', 'GONZALEZ'];

        $ultimoDigito = substr($dni, -1);
        $genero = ($ultimoDigito % 2 == 0) ? 'FEMENINO' : 'MASCULINO';

        if ($genero === 'FEMENINO') {
            $nombres = ['MARIA ELENA', 'LUISA FERNANDA', 'ANA SOFIA', 'CARLA PATRICIA'];
            $nombreSeleccionado = $nombres[$dni[6] % 4] ?? 'NOMBRES';
        } else {
            $nombres = ['JUAN CARLOS', 'PEDRO ANTONIO', 'LUIS ALBERTO', 'JOSE MANUEL'];
            $nombreSeleccionado = $nombres[$dni[6] % 4] ?? 'NOMBRES';
        }

        return response()->json([
            'success' => true,
            'datos' => [
                'nombres' => $nombreSeleccionado,
                'apellido_paterno' => $apellidosFicticios[$dni[5] % 5] ?? 'GARCIA',
                'apellido_materno' => $apellidosFicticios[$dni[4] % 5] ?? 'RODRIGUEZ',
                'nombre_completo' => $nombreSeleccionado . ' ' . ($apellidosFicticios[$dni[5] % 5] ?? 'GARCIA') . ' ' . ($apellidosFicticios[$dni[4] % 5] ?? 'RODRIGUEZ'),
                'fecha_nacimiento' => '199' . ($dni[2] % 10) . '-0' . (($dni[3] % 9) + 1) . '-' . str_pad(($dni[4] % 28) + 1, 2, '0', STR_PAD_LEFT),
                'genero' => $genero,
                'celular' => '9' . substr($dni, 0, 8),
                'direccion' => 'AV. EJEMPLO ' . ($dni[4] * 100) . ' MZ ' . ($dni[5] + 1) . ' LT ' . ($dni[6] + 1),
                'email' => 'dni' . $dni . '@ejemplo.com',
                'foto' => ''
            ],
            'modo_prueba' => true,
            'mensaje' => 'Usando datos de prueba para desarrollo'
        ]);
    }

    /**
     * Función para verificar si el rondero ya existe por DNI
     */
    private function existsRonderoByDNI($dni)
    {
        return Rondero::whereHas('persona', function ($query) use ($dni) {
            $query->where('docIdentidad', $dni);
        })->exists();
    }

    /**
     * Consulta de requisitoriados
     * La URL se toma de la base de datos
     */
    public function ConsultarRQ(Request $request)
    {
        // 🔥 OBTENER URL DE LA BASE DE DATOS
        $url = Configuracion::get('CONSULTA_RQ_URL');

        if (!$url) {
            $url = 'https://sispasvehapp.mininter.gob.pe/api-recompensas/requisitoriados';
        }

        $nombres_apellidos = $request->input('nombreCompleto');

        Log::info('🔍 Consulta RQ iniciada', ['url' => $url, 'nombre' => $nombres_apellidos]);

        if ($nombres_apellidos != '') {
            $payload = [
                'pageInfo' => [
                    'page' => 1,
                    'size' => 4,
                    'sortBy' => 'id',
                    'direction' => 'desc',
                ],
                'search' => [
                    'nombreCompleto' => $request->input('nombreCompleto'),
                    'tipoFilter' => 'F',
                ],
            ];

            try {
                $response = Http::withOptions(['verify' => false])->post($url . '/pageandfilter', $payload);

                if ($response->successful()) {
                    if (count($response->json()['content']) > 0) {
                        $hashRequisitoriado = $response->json()['content'][0]['hashRequisitoriado'];
                        $urlRequisitoriado = $url . '/' . $hashRequisitoriado;
                        $responseRequisitoriado = Http::withOptions(['verify' => false])->get($urlRequisitoriado);

                        AuditoriaService::registrarConsultaRequisitoriado(
                            $nombres_apellidos,
                            $responseRequisitoriado->json(),
                            true
                        );

                        return response()->json([
                            'message' => 'Datos enviados correctamente',
                            'data' => $responseRequisitoriado->json(),
                        ]);
                    } else {
                        AuditoriaService::registrarConsultaRequisitoriado(
                            $nombres_apellidos,
                            ['message' => 'No se encontraron datos'],
                            false
                        );

                        return response()->json([
                            'message' => 'No se encontraron datos',
                            'status' => $response->status(),
                            'error' => 'SIN DATOS',
                        ]);
                    }
                } else {
                    AuditoriaService::registrarConsultaRequisitoriado(
                        $nombres_apellidos,
                        ['error' => 'API error: ' . $response->status()],
                        false
                    );

                    return response()->json([
                        'message' => 'Error en la solicitud',
                        'status' => $response->status(),
                        'error' => $response->body(),
                    ], $response->status());
                }
            } catch (Exception $e) {
                AuditoriaService::registrarConsultaRequisitoriado(
                    $nombres_apellidos,
                    ['error' => $e->getMessage()],
                    false
                );

                return response()->json([
                    'message' => 'Error al enviar la solicitud',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json([
                'message' => 'No se encontraron datos',
            ]);
        }
    }
}
