<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wildside\Userstamps\Userstamps;

class Distrito extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.distrito';

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function sectores(): HasMany
    {
        return $this->hasMany(Sector::class, 'distrito_id');
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
