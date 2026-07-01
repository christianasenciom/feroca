<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Publico\Rondero;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class AuditoriaService
{
    public static function registrarMovimiento(array $meta, bool $exitoso = true)
    {
        $descripcion = $meta['accion'] ?? $meta['ruta'] ?? 'movimiento';

        return self::registrar([
            'tipo_consulta' => 'movimiento',
            'dni_consultado' => null,
            'nombre_consultado' => $descripcion,
            'datos_consulta' => $meta,
            'encontrado' => $exitoso,
        ]);
    }

    public static function registrarConsultaReniec($dni, $datos, $encontrado = true)
    {
        Log::info('📝 [RENIEC] Iniciando registro de auditoría', [
            'dni' => $dni,
            'encontrado' => $encontrado,
            'user_id' => Auth::id()
        ]);

        $nombreConsultado = null;
        if (isset($datos['nombre_completo'])) {
            $nombreConsultado = $datos['nombre_completo'];
        } elseif (isset($datos['nombres'])) {
            $apellidoPaterno = $datos['apellido_paterno'] ?? '';
            $apellidoMaterno = $datos['apellido_materno'] ?? '';
            $nombres = $datos['nombres'] ?? '';
            $nombreConsultado = trim($nombres . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno);
        }

        return self::registrar([
            'tipo_consulta' => 'reniec',
            'dni_consultado' => $dni,
            'nombre_consultado' => $nombreConsultado,
            'datos_consulta' => $datos,
            'encontrado' => $encontrado
        ]);
    }

    public static function registrarConsultaRequisitoriado($nombre, $datos, $encontrado = true)
    {
        Log::info('📝 [REQUISITORIADO] Iniciando registro de auditoría', [
            'nombre' => $nombre,
            'encontrado' => $encontrado,
            'user_id' => Auth::id()
        ]);

        return self::registrar([
            'tipo_consulta' => 'requisitoriado',
            'dni_consultado' => null,
            'nombre_consultado' => $nombre,
            'datos_consulta' => $datos,
            'encontrado' => $encontrado
        ]);
    }

    private static function registrar($data)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Log::warning('⚠️ No hay usuario autenticado');
                return null;
            }

            Log::info('👤 Usuario autenticado', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email
            ]);

            // Buscar rondero
            $rondero = null;
            if ($user->persona_id) {
                $rondero = Rondero::where('persona_id', $user->persona_id)->first();
                if ($rondero) {
                    Log::info('✅ Rondero encontrado', [
                        'rondero_id' => $rondero->id,
                        'base_id' => $rondero->base_id
                    ]);
                } else {
                    Log::warning('⚠️ No se encontró rondero para persona_id: ' . $user->persona_id);
                }
            }

            $registro = [
                'user_id' => $user->id,
                'ip_usuario' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ];

            if ($rondero) {
                $registro['rondero_id'] = $rondero->id;
                $registro['region_id'] = $rondero->region_id;
                $registro['provincia_id'] = $rondero->provincia_id;
                $registro['distrito_id'] = $rondero->distrito_id;
                $registro['sector_zona_id'] = $rondero->sector_zona_id;
                $registro['base_id'] = $rondero->base_id;
            }

            $registro = array_merge($registro, $data);

            Log::info('💾 Datos a guardar en auditoría', $registro);

            $auditoria = Auditoria::create($registro);

            Log::info('✅ Auditoría guardada exitosamente', ['id' => $auditoria->id]);

            return $auditoria;

        } catch (\Exception $e) {
            Log::error('❌ Error al guardar auditoría: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
