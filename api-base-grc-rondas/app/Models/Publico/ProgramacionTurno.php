<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wildside\Userstamps\Userstamps;

class ProgramacionTurno extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.programacion_turno';

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }


    public function rondero()
    {
        return $this->belongsTo(Rondero::class);
    }

}
