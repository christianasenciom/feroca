<?php

namespace App\Exports\Reportes;

use App\Http\Resources\Publico\DetalleDenunciaResource;
use App\Http\Resources\Publico\Reportes\DenunciasFechasResource;
use App\Models\Publico\Denuncia;
use App\Models\Publico\Denunciado;
use App\Models\Publico\Persona;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DenunciasPersonaExport implements FromView, ShouldAutoSize
{

    private $fechaInicio;
    private $fechaFin;

    public function __construct($type, $keyword)
    {
        $this->type = $type;
        $this->keyword = $keyword;
    }


    public function view(): View
    {
        $resultados = array();

        $persona = '';
        $denuncias = collect();
        switch ($this->type) {
            case 'DENUNCIADO':
                $denunciado = Denunciado::whereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres) iLIKE ?", ["%$this->keyword%"])->first();

                if ($denunciado) {
                    $denuncias = Denuncia::whereHas('listaDenunciados.denunciado', function ($q) use ($denunciado) {
                        $q->where('denunciado_id', $denunciado->id);
                    })->with('denunciante', 'conflicto')->get();
                } else {
                    $denuncias = collect(); // Empty collection if no denunciado found
                }

                $persona = $denunciado->nombres . ' ' . $denunciado->apellido_paterno . ' ' . $denunciado->apellido_materno;
                break;
            case 'DENUNCIANTE':
                $denunciante = Persona::where('docIdentidad', $this->keyword)->first();

                if ($denunciante) {
                    $denuncias = $denunciante->denuncias()->with('denunciante', 'conflicto')->get();
                } else {
                    $denuncias = collect(); // Empty collection if no denunciante found
                }
                $persona = $denunciante->nombres . ' ' . $denunciante->apellido_paterno . ' ' . $denunciante->apellido_materno;
            default:
                break;
        }

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
        return view('excel.denuncias_por_persona', compact('resultados', 'persona'));

    }
}
