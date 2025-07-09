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
    protected $table = 'public.base';

    public function sector()
    {
        return $this->belongsTo(Sector::class,'sector_zona_id','id');
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
        return $this->belongsTo(Persona::class, 'admin_id','id');
    }
}
