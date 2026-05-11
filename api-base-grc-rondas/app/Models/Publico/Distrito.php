<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wildside\Userstamps\Userstamps;

class Distrito extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.distrito';

    protected $fillable = [
        'descripcion',
        'provincia_id',
        'estado'
    ];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id', 'id');
    }

    public function sectores(): HasMany
    {
        return $this->hasMany(Sector::class, 'distrito_id', 'id');
    }

    // Nueva relación con bases (cuando no están en un sector)
    public function basesDirectas(): HasMany
    {
        return $this->hasMany(Base::class, 'distrito_id', 'id')
                    ->whereNull('sector_zona_id');
    }

    // Todas las bases relacionadas con este distrito (directas o a través de sectores)
    public function todasBases()
    {
        return Base::where('distrito_id', $this->id);
    }

    public function rondero(): HasMany
    {
        return $this->hasMany(Rondero::class, 'distrito_id');
    }

    public function comites()
    {
        return $this->morphMany(Comite::class, 'comiteable');
    }
}
