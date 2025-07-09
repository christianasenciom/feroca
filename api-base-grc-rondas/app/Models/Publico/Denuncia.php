<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Publico\Persona;
use App\Models\Publico\Conflicto;
use Wildside\Userstamps\Userstamps;

class Denuncia extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.denuncia';

    protected $fillable = [
        'id',
        'fecha',
        'descripcion',
        'base_id',
        'denunciante_id',
        'tipo_conflicto_id',
        'libro',
        'hoja',
        'num_denuncia',
        'fecha_cita',
        'observaciones',
        'estado',
        'region_id',
        'provincia_id',
        'ditrito_id',
        'sector_id',
        'base_id',
    ];

    public function denunciante()
    {
        return $this->belongsTo(Persona::class, 'denunciante_id');
    }

    public function conflicto()
    {
        return $this->belongsTo(Conflicto::class, 'tipo_conflicto_id');
    }

    public function listaDenunciados()
    {
        return $this->hasMany(DetalleDenuncia::class, 'denuncia_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'denuncia_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class, 'distrito_id');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function base()
    {
        return $this->belongsTo(Base::class, 'base_id');
    }
}


