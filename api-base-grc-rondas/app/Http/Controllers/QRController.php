<?php
namespace App\Http\Controllers;

use App\Http\Resources\Publico\DataCarnetResource;
use App\Models\Publico\Rondero;
use Illuminate\Http\Request;

class QRController extends Controller
{
    public function validarQr($qr)
    {
        $qrData = $qr;

        // Verifica si el QR es válido

        // Realiza la validación según la lógica de tu aplicación.
        // Por ejemplo, verifica si el QR corresponde a un registro válido en tu base de datos:
        $rondero = Rondero::where('codigo_qr', $qrData)->first();
        if (!$rondero) {
            return response()->json([
                'valid' => false,
            ]);
        }
        // Verifica si el rondero está habilitado
        $habilitado = $rondero->isHabilitado();
        return response()->json([
            'valid' => true,
            'habilitado' => $habilitado,
            'datos' => new DataCarnetResource($rondero),
        ]);
    }
}
