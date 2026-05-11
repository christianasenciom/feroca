<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Base extends Model
{
    use HasFactory, Userstamps;
    protected $connection = 'pgsql';
    protected $table = 'public.base';

    protected $fillable = [
        'nombre_descripcion',
        'numero_partida_registral',
        'region_id',
        'provincia_id',
        'distrito_id',
        'sector_zona_id', // Opcional
        'estado',
        'admin_id'
    ];

    protected $casts = [
        'numero_partida_registral' => 'string'
    ];

    // Validación para número de partida registral de 8 dígitos
    public static function rules()
    {
        return [
            'numero_partida_registral' => 'nullable|digits:8',
            'sector_zona_id' => 'nullable|exists:public.sector_zona,id',
            'distrito_id' => 'required|exists:public.distrito,id',
            'provincia_id' => 'required|exists:public.provincia,id',
            'region_id' => 'required|exists:public.region,id',
        ];
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_zona_id', 'id');
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class, 'distrito_id', 'id');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id', 'id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function rondero(): HasMany
    {
        return $this->hasMany(Rondero::class, 'base_id');
    }
    
    public function comites()
    {
        return $this->morphMany(Comite::class, 'comiteable');
    }
    
    public function admin()
    {
        return $this->belongsTo(Persona::class, 'admin_id', 'id');
    }

    // Scope para filtrar por región
    public function scopePorRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    // Scope para filtrar por provincia
    public function scopePorProvincia($query, $provinciaId)
    {
        return $query->where('provincia_id', $provinciaId);
    }

    // Scope para filtrar por distrito
    public function scopePorDistrito($query, $distritoId)
    {
        return $query->where('distrito_id', $distritoId);
    }
}