<?php

namespace App\Exports\Reportes;

use App\Http\Resources\Publico\DetalleDenunciaResource;
use App\Models\Publico\Denuncia;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DenunciasFechasExport implements FromView, ShouldAutoSize
{

    private $fechaInicio;
    private $fechaFin;

    public function __construct($fechaInicio, $fechaFin)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }


    public function view(): View
    {
        $resultados = array();

        $denuncias = Denuncia::query();
        if ($this->fechaInicio && $this->fechaFin) {
            $denuncias = $denuncias->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
        }

        $denuncias = $denuncias->with('denunciante', 'conflicto')->get();

        foreach ($denuncias as $item) {
            array_push(
                $resultados,
                [
                    'fecha' => date('d/m/Y', strtotime($item->fecha)),
                    'tipo_conflicto' => $item->conflicto->descripcion,
                    'num_denuncia' => $item->num_denuncia,
                    'denunciante' => $item->denunciante->nombres . ' ' . $item->denunciante->apellido_paterno . ' ' . $item->denunciante->apellido_materno,
                    'listaDenunciados' =>  DetalleDenunciaResource::collection($item->listaDenunciados),
                    'estado_denuncia' => $item->estado_denuncia,
                    'descripcion' => $item->descripcion,

                ]
            );
        }

        $resultados  = json_decode(json_encode($resultados));
        $fechaInicio = $this->fechaInicio;
        $fechaFin = $this->fechaFin;
        return view('excel.denuncias_por_fechas', compact('resultados','fechaInicio','fechaFin'));
    }
}
