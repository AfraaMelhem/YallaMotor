<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $guarded = ['id'];
    protected $hidden = ['pivot', 'created_at', 'updated_at'];
    protected $guard_name = 'api';


}

