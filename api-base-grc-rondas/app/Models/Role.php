<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as RolSpatie;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Wildside\Userstamps\Userstamps;

class Role extends RolSpatie
{
    use UserStamps;

    protected $table = 'auth.roles';
    public $guard_name = 'api';

    protected $fillable = [
        'id',
        'name',
        'guard_name'
    ];
}
