<?php

namespace App\Http\Controllers;

use App\Models\Publico\Persona;
use App\Models\Publico\Rondero;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UtilsController extends Controller
{

    /**
     * @throws Exception
     */
    public function getAOuthTokenDNI()
    {
        // Verificar si hay un token en caché que aún no ha expirado
        $tokenData = Cache::get('service_token');
        if ($tokenData && $tokenData['expires_at'] > now()) {
            return $tokenData['access_token'];
        }

        // Obtener un nuevo token porque no existe o ha expirado
        $response = Http::asForm()->post(env('RENIEC_GRC_AOUTH_URL_TOKEN'), [
            'grant_type' => env('RENIEC_GRC_GRANT_TYPE'),
            'client_id' => env('RENIEC_GRC_AOUTH_CLIENT_ID'),
            'client_secret' => env('RENIEC_GRC_AOUTH_CLIENT_SECRET'),
        ]);

        $data = $response->json();

        if (!isset($data['access_token'], $data['expires_in'])) {
            throw new \Exception('No se pudo obtener el token');
        }

        // Calcular la fecha de expiración usando expires_in (en segundos)
        $expiresAt = now()->addSeconds($data['expires_in']);


        // Guardar el token y su tiempo de expiración en el caché
        Cache::put('service_token', [
            'access_token' => $data['access_token'],
            'expires_at' => $expiresAt,
        ], $expiresAt);

        return $data['access_token'];
    }

    public function getDataDNI($dni, $tipo_persona)
    {
        //$tipo_persona = 'RONDERO';
        //$tipo_persona = 'PERSONA';
        try {
            //validar $dni
            if (is_numeric($dni)) {
                if (strlen($dni) != 8) {
                    $message = [
                        'title' => 'Error en validación de datos',
                        'message' => 'El número de DNI, no es válido',
                    ];
                    return response()->json(['errors' => $message], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $message = [
                    'title' => 'Error en validación de datos',
                    'message' => 'Por favor, ingrese un número de DNI valido',
                ];
                return response()->json(['errors' => $message], Response::HTTP_BAD_REQUEST);
            }
            if ($tipo_persona == 'RONDERO') {
                if ($this->existsRonderoByDNI($dni)) {
                    $message = [
                        'title' => 'Error en validación de datos',
                        'message' => 'Ya existe un rondero con el DNI ' . $dni,
                    ];
                    return response()->json($message, Response::HTTP_BAD_REQUEST);
                }
            }
//apis-token-15583.DTAaAYKuLIlMJC024VubYPkdZn5ysN8u
            // Verificamos si ya existe un usuario con el DNI proporcionado}
            $persona_find = Persona::where('docIdentidad', $dni)->first();
            if($persona_find) {
                $persona = [
                    'id' => $persona_find->id,
                    'numero' => $persona_find->docIdentidad,
                    'apellidoPaterno' => $persona_find->apellido_paterno,
                    'apellidoMaterno' => $persona_find->apellido_materno,
                    'nombres' => $persona_find->nombres,
                    'sexo' => substr($persona_find->genero, 0, 1) ?? 'M',
                    'nacimiento' => $persona_find->fecha_nacimiento,
                    'distrito_id' => '',
                    'direccion' => $persona_find->direccion,
                    'referencia' => '',
                    'fono' => $persona_find->celular,
                    'correo' => $persona_find->email,
                    'fecha_consulta_reniec' => '',
                    'distrito_code' => '',
                    'foto' => $persona_find->foto,
                ];
                return response()->json($persona);
            }
            // Si la persona no existe, se obtiene el token para poder obtener la información
            //obtener token
            $token = env('RENIEC_GRC_AOUTH_CLIENT_SECRET');
            $url = env("RENIEC_GRC_API_GET_DATA").$dni."&token=".$token;
        
           // $response = Http::asJson()
             //   ->post(env('RENIEC_GRC_API_GET_DATA').$dni.'?token='.$token);
            $response = Http::withOptions(['verify' => false])->get($url);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['errors' => 'Error al obtener la información del RENIEC'], 500);
        }

    }
    // Función para verificar si el rondero ya existe por DNI
    private function existsRonderoByDNI($dni)
    {
        return Rondero::whereHas('persona', function ($query) use ($dni) {
            $query->where('docIdentidad', $dni);
        })->exists();
    }

    public function ConsultarRQ(Request $request)
    {
        // Obtiene la URL desde el archivo .env
        $url = env('API_RECOMPENSAS_URL');
        $nombres_apellidos = $request->input('nombreCompleto');

        if ($nombres_apellidos != '') {
            // Datos que deseas enviar
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
                // Envía la solicitud POST
                $response = Http::withOptions(['verify' => false])->post($url.'/pageandfilter', $payload);

                // Maneja la respuesta
                if ($response->successful()) {
                    if (count($response->json()['content']) > 0) {
                        $hashRequisitoriado = $response->json()['content'][0]['hashRequisitoriado'];
                        $urlRequisitoriado = $url.'/'.$hashRequisitoriado;
                        $responseRequisitoriado = Http::withOptions(['verify' => false])->get($urlRequisitoriado);
                        return response()->json([
                            'message' => 'Datos enviados correctamente',
                            'data' => $responseRequisitoriado->json(),
                        ]);
                    }
                    else {
                        return response()->json([
                            'message' => 'No se encontraron datos',
                            'status' => $response->status(),
                            'error' => 'SIN DATOS',
                        ]);
                    }
                } else {
                    return response()->json([
                        'message' => 'Error en la solicitud',
                        'status' => $response->status(),
                        'error' => $response->body(),
                    ], $response->status());
                }
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Error al enviar la solicitud',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }else{
            return response()->json([
                'message' => 'No se encontraron datos',
            ]);
        }

    }
}
