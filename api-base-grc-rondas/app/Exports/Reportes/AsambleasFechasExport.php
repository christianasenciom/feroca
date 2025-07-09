<?php

namespace App\Exports\Reportes;

use App\Http\Resources\Publico\Reportes\DenunciasFechasResource;
use App\Models\Publico\Denuncia;
use App\Models\Publico\Turno;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AsambleasFechasExport implements FromView, ShouldAutoSize
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

        $asambleas = Turno::query()->where('tipo_reunion', 'Asamblea');
        if ($this->fechaInicio && $this->fechaFin) {
            $asambleas = $asambleas->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin])->get();
        }

        foreach ($asambleas as $item) {
            array_push(
                $resultados,
                [
                    'fecha' => date('d/m/Y', strtotime($item->fecha)),
                ]
            );
        }

        $resultados  = json_decode(json_encode($resultados));
        $fechaInicio = $this->fechaInicio;
        $fechaFin = $this->fechaFin;
        return view('excel.denuncias_por_fechas', compact('resultados','fechaInicio','fechaFin'));
    }
}
