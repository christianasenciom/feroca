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
     * Obtener la configuración actual de RENIEC
     */
    public function getReniecConfig()
    {
        try {
            \Log::info('getReniecConfig called');

            $data = [
                'RENIEC_REST_URL' => Configuracion::get('RENIEC_REST_URL'),
                'RENIEC_DNI_USUARIO' => Configuracion::get('RENIEC_DNI_USUARIO'),
                'RENIEC_PASSWORD' => Configuracion::get('RENIEC_PASSWORD'),
                'RENIEC_RUC_USUARIO' => Configuracion::get('RENIEC_RUC_USUARIO'),
                'RENIEC_TIMEOUT' => Configuracion::get('RENIEC_TIMEOUT', 60),
            ];

            \Log::info('Config data:', $data);

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('Error en getReniecConfig: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar la configuración de RENIEC
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

        Log::info('Configuración de RENIEC actualizada por: ' . auth()->user()->name);

        return response()->json([
            'success' => true,
            'message' => 'Credenciales de RENIEC actualizadas correctamente'
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

        return response()->json([
            'success' => true,
            'message' => 'Valores por defecto cargados correctamente'
        ]);
    }

    /**
     * Probar la conexión con las credenciales actuales
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'dni' => 'required|string|size:8'
        ]);

        $url = Configuracion::get('RENIEC_REST_URL');
        $usuario = Configuracion::get('RENIEC_DNI_USUARIO');
        $password = Configuracion::get('RENIEC_PASSWORD');
        $timeout = Configuracion::get('RENIEC_TIMEOUT', 60);

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['verify' => false])
                ->post($url, [
                    'dni' => $request->dni,
                    'usuario' => $usuario,
                    'password' => $password
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión exitosa',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en la conexión: ' . $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error probando conexión RENIEC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }
}
