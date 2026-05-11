<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission as PermissionSpatie;
use Wildside\Userstamps\Userstamps;

class Permission extends PermissionSpatie
{
    use UserStamps;

    protected $table = 'auth.permissions';
    public $guard_name = 'api';

    protected $fillable = [
        'name',
        'guard_name',
    ];

    protected $dates = ['created_at'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
}
