<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

class DetalleDenuncia extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.detalle_denuncia';

    public function denunciado()
    {
        return $this->belongsTo(Denunciado::class, 'denunciado_id');
    }


}
