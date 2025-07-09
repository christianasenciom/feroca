<?php

namespace App\Exports;

use App\Models\Permission;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PermissionExport implements FromQuery, WithTitle, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    private $current_row = 1;
    private $keyBuscar;

    // pasamos el parametro de consulta
    public function withBuscar(
        string $keyBuscar
    )
    {
        $this->keyBuscar = $keyBuscar;

        return $this;
    }


    /**
     * @return Builder
     */
    public function query()
    {

        $data = Permission::query();

        if ($this->keyBuscar != "keyBuscar") {
            $data = $data->where("name", "like", "%".$this->keyBuscar."%");
        }

        return $data->orderBy("name");
    }


    /**
     * @return string
     */
    public function title(): string
    {
        return 'Permisos';
    }

    /**
     * @return string for Headings
     */
    public function headings(): array
    {
        return [
            'NRO',
            'NOMBRE',
            'GUARD',
        ];
    }


    /**
     * @return array
     */
    public function map($row): array
    {
        return [
            $this->current_row++,
            $row->name,
            $row->guard_name,
        ];
    }
}
