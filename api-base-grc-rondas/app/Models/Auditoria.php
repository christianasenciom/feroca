<?php

namespace App\Models;

use App\Models\Base\User;
use App\Models\Publico\Rondero;
use App\Models\Publico\Region;
use App\Models\Publico\Provincia;
use App\Models\Publico\Distrito;
use App\Models\Publico\Sector;
use App\Models\Publico\Base;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditoria';

    protected $fillable = [
        'tipo_consulta',
        'dni_consultado',
        'nombre_consultado',
        'datos_consulta',
        'ip_usuario',
        'user_agent',
        'user_id',
        'rondero_id',
        'region_id',
        'provincia_id',
        'distrito_id',
        'sector_zona_id',
        'base_id',
        'encontrado'
    ];

    protected $casts = [
        'datos_consulta' => 'array',
        'encontrado' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rondero()
    {
        return $this->belongsTo(Rondero::class, 'rondero_id');
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
        return $this->belongsTo(Sector::class, 'sector_zona_id');
    }

    public function base()
    {
        return $this->belongsTo(Base::class, 'base_id');
    }
}
