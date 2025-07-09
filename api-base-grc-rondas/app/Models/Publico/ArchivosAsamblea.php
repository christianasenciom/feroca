<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wildside\Userstamps\Userstamps;

class ArchivosAsamblea extends Model
{
    use HasFactory, Userstamps;
    public $timestamps = true;
    protected $table = 'public.archivos_asamblea';
    protected $guarded = [];

    public function asamblea(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }
}
