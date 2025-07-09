<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

class Notificacion extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.notificacion';

    public function denuncia()
    {
        return $this->belongsTo(Denuncia::class, 'denuncia_id','id');
    }
}
