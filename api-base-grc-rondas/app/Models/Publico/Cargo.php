<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

class Cargo extends Model
{
    use HasFactory, Userstamps;
    protected $table = 'public.cargo';

    protected $fillable = [
        'id',
        'descripcion',
        'estado'
    ];

    public function comites()
    {
        return $this->hasMany(Comite::class);
    }

}
