<?php

namespace App\Models\Publico;

use App\Models\Base\User;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Persona extends Model
{
    use HasFactory, Userstamps;
    protected $table = 'public.personas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'documento_tipo',
        'docIdentidad',
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'fecha_nacimiento',
        'genero',
        'celular',
        'direccion',
        'email',
        'tipo',
        'foto',
    ];

    protected $appends = [
        'full_name',
        'fecha_nacimiento_es',
    ];

    public function getFullNameAttribute()
    {
        if ($this->tipo == 'Natural') {
            return trim($this->apellido_paterno . ' ' . $this->apellido_materno . ' ' . $this->nombres);
        } else {
            return $this->nombres;
        }
    }

    /**
     * creando el atributo estado.
     */
    public function getFechaNacimientoEsAttribute()
    {
        return date("d-m-Y", strtotime($this->fecha_nacimiento));
    }

    /**
     * Relaciones
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'persona_id');
    }
    public function rondero(): HasOne
    {
        return $this->hasOne(Rondero::class, 'persona_id');
    }

    public function denuncias()
    {
        return $this->hasMany(Denuncia::class, 'denunciante_id');
    }

}
