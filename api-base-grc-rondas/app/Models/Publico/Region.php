<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Publico\Provincia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wildside\Userstamps\Userstamps;

class Region extends Model
{
    use HasFactory, Userstamps;
    protected $table = 'public.region';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'descripcion',
        'estado'
    ];


    /**
     * Relaciones
     */
    public function provincias(): HasMany
    {
        return $this->hasMany(Provincia::class, 'region_id');
    }
    public function rondero(): HasMany
    {
        return $this->hasMany(Rondero::class, 'region_id');
    }
    public function comites()
    {
        return $this->morphMany(Comite::class, 'comiteable');
    }
}
