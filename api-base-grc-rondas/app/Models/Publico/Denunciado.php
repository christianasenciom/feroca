<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

class Denunciado extends Model
{
    use HasFactory, Userstamps;
    protected $table = 'public.denunciado';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // 'documento_tipo',
        // 'docIdentidad',
        'id',
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'celular',
        'direccion'
    ];

    protected $appends = [
        'full_name',
    ];

    public function getFullNameAttribute()
    {
        return trim($this->apellido_paterno . ' ' . $this->apellido_materno . ' ' . $this->nombres);
    }

    /**
     * Relaciones
     */


    public function denuncias()
    {
        return $this->hasMany(Denuncia::class);
    }
}
