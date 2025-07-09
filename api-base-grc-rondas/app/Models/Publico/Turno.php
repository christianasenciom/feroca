<?php

namespace App\Models\Publico;

use App\Models\Infracciones\FilesMulta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wildside\Userstamps\Userstamps;

class Turno extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.turno_ronda';
    protected $fillable = [
        'id',
        'fecha',
        'descripcion',
        'base_id',
        'foto',
        'tipo_reunion',
        'responsable_turno',
        'sector_zona_id',
        'distrito_id',
        'provincia_id',
        'region_id',

    ];
    public function listaRonderos()
    {
        return $this->hasMany(ProgramacionTurno::class, 'turno_id');
    }
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }
    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
    public function sector_zona()
    {
        return $this->belongsTo(Sector::class);
    }
    public function base()
    {
        return $this->belongsTo(Base::class);
    }
    public function responsable()
    {
        return $this->belongsTo(Rondero::class, 'responsable_turno');
    }

    public function archivos_asamblea (): HasMany
    {
        return $this->hasMany(ArchivosAsamblea::class, 'turno_id');
    }

}
