<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wildside\Userstamps\Userstamps;

class Sector extends Model
{
    use HasFactory, Userstamps;
    protected $connection = 'pgsql';
    protected $table = 'public.sector_zona';

    protected $fillable = [
        'descripcion',
        'distrito_id',
        'estado',
        'admin_id'
    ];

    // CORRECCIÓN: La relación estaba al revés
    public function bases(): HasMany
    {
        return $this->hasMany(Base::class, 'sector_zona_id', 'id');
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class, 'distrito_id', 'id');
    }

    public function rondero(): HasMany
    {
        return $this->hasMany(Rondero::class, 'sector_zona_id');
    }

    public function comites()
    {
        return $this->morphMany(Comite::class, 'comiteable');
    }

    public function admin()
    {
        return $this->belongsTo(Persona::class, 'admin_id', 'id');
    }
}
