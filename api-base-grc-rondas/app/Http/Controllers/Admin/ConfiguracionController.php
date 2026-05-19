<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfiguracionController extends Controller
{
    /**
     * Obtener toda la configuración
     */
    public function getAll()
    {
        try {
            return response()->json([
                'RENIEC_REST_URL' => Configuracion::get('RENIEC_REST_URL') ?: '',
                'RENIEC_DNI_USUARIO' => Configuracion::get('RENIEC_DNI_USUARIO') ?: '',
                'RENIEC_PASSWORD' => Configuracion::get('RENIEC_PASSWORD') ?: '',
                'RENIEC_RUC_USUARIO' => Configuracion::get('RENIEC_RUC_USUARIO') ?: '',
                'RENIEC_TIMEOUT' => (int) Configuracion::get('RENIEC_TIMEOUT', 60),
                'CONSULTA_RQ_URL' => Configuracion::get('CONSULTA_RQ_URL') ?: '',
            ]);
        } catch (\Exception $e) {
            Log::error('Error en getAll: ' . $e->getMessage());
            return response()->json([
                'RENIEC_REST_URL' => '',
                'RENIEC_DNI_USUARIO' => '',
                'RENIEC_PASSWORD' => '',
                'RENIEC_RUC_USUARIO' => '',
                'RENIEC_TIMEOUT' => 60,
                'CONSULTA_RQ_URL' => '',
            ]);
        }
    }

    /**
     * Actualizar configuración de RENIEC
     */
    public function updateReniec(Request $request)
    {
        $request->validate([
            'RENIEC_REST_URL' => 'required|url',
            'RENIEC_DNI_USUARIO' => 'required|string|size:8',
            'RENIEC_PASSWORD' => 'required|string|min:6',
            'RENIEC_RUC_USUARIO' => 'required|string|size:11',
            'RENIEC_TIMEOUT' => 'required|integer|min:10|max:300',
        ]);

        Configuracion::set('RENIEC_REST_URL', $request->RENIEC_REST_URL);
        Configuracion::set('RENIEC_DNI_USUARIO', $request->RENIEC_DNI_USUARIO);
        Configuracion::set('RENIEC_PASSWORD', $request->RENIEC_PASSWORD);
        Configuracion::set('RENIEC_RUC_USUARIO', $request->RENIEC_RUC_USUARIO);
        Configuracion::set('RENIEC_TIMEOUT', $request->RENIEC_TIMEOUT);



        return response()->json([
            'success' => true,
            'message' => 'Configuración de RENIEC actualizada correctamente'
        ]);
    }

    /**
     * Actualizar configuración de Consulta RQ
     */
    public function updateConsultaRQ(Request $request)
    {
        $request->validate([
            'CONSULTA_RQ_URL' => 'required|url',
        ]);

        Configuracion::set('CONSULTA_RQ_URL', $request->CONSULTA_RQ_URL);



        return response()->json([
            'success' => true,
            'message' => 'URL de Consulta RQ actualizada correctamente'
        ]);
    }

    /**
     * Cargar valores por defecto
     */
    public function seedDefaults()
    {
        Configuracion::set('RENIEC_REST_URL', 'https://ws2.pide.gob.pe/Rest/RENIEC/Consultar');
        Configuracion::set('RENIEC_DNI_USUARIO', '41884337');
        Configuracion::set('RENIEC_PASSWORD', 'C0nsultDni100426..');
        Configuracion::set('RENIEC_RUC_USUARIO', '20453744168');
        Configuracion::set('RENIEC_TIMEOUT', '60');
        Configuracion::set('CONSULTA_RQ_URL', 'https://sispasvehapp.mininter.gob.pe/api-recompensas/requisitoriados');

        return response()->json([
            'success' => true,
            'message' => 'Valores por defecto cargados correctamente'
        ]);
    }
    public function actualizarCredencial(Request $request)
    {
        $request->validate([
            'credencialAnterior' => 'required|string',
            'credencialNueva' => 'required|string|min:6',
            'nuDni' => 'required|string|size:8',
            'nuRuc' => 'required|string|size:11',
        ]);

        $url = 'https://ws2.pide.gob.pe/Rest/RENIEC/Actualizar';

        try {
            $response = Http::timeout(60)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=UTF-8'
                ])
                ->post($url, [
                    'PIDE' => [
                        'credencialAnterior' => $request->credencialAnterior,
                        'credencialNueva' => $request->credencialNueva,
                        'nuDni' => $request->nuDni,
                        'nuRuc' => $request->nuRuc,
                    ]
                ]);

            $body = $response->body();

            // Extraer valores usando expresiones regulares
            preg_match('/<coResultado>(.*?)<\/coResultado>/', $body, $coMatch);
            preg_match('/<deResultado>(.*?)<\/deResultado>/', $body, $deMatch);

            $coResultado = $coMatch[1] ?? '9999';
            $deResultado = $deMatch[1] ?? '';


            if ($coResultado === '0000') {
                // Actualizar la contraseña en la base de datos
                Configuracion::set('RENIEC_PASSWORD', $request->credencialNueva);

                return response()->json([
                    'success' => true,
                    'message' => "Contraseña actualizada correctamente de \"{$request->credencialAnterior}\" a \"{$request->credencialNueva}\"",
                    'old_password' => $request->credencialAnterior,
                    'new_password' => $request->credencialNueva
                ], 200); // 🔥 Código 200 OK
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $deResultado ?: 'Error al actualizar la credencial'
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('Error actualizando credencial RENIEC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }
}
