<?php

namespace App\Models\Publico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

class Conflicto extends Model
{
    use HasFactory, Userstamps;

    protected $table = 'public.conflicto';
}
