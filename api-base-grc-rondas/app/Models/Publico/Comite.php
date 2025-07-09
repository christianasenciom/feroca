<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

class Comite extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.comite';
    protected $fillable = [
        'id',
        'rondero_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'cargo_id',
        'comiteable_id',
        'comiteable_type',

    ];

    public function comiteable()
    {
        return $this->morphTo();
    }

    public function rondero()
    {
        return $this->belongsTo(Rondero::class);
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }
}
