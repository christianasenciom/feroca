<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sector extends Model
{
    use HasFactory, Userstamps;
    protected $table = 'public.sector_zona';

    public function bases(): HasMany
    {
        return $this->hasMany(Base::class,'id','sector_zona_id');
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
    public function rondero(): HasMany
    {
        return $this->hasMany(Rondero::class,'sector_zona_id');
    }
    public function comites()
    {
        return $this->morphMany(Comite::class, 'comiteable');
    }
    public function admin()
    {
        return $this->belongsTo(Persona::class, 'admin_id','id');
    }
}
