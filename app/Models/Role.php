<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name',
        'guard_name',
        ];
    protected $hidden = ['pivot', 'created_at', 'updated_at'];
    protected $guard_name = 'api';


}
