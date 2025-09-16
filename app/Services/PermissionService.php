<?php


namespace App\Services;

use App\Repositories\Interfaces\PermissionRepositoryInterface;

class PermissionService
{
    protected $permissionRepositoryInterface;

    public function __construct(PermissionRepositoryInterface $permissionRepositoryInterface)
    {
        $this->permissionRepositoryInterface = $permissionRepositoryInterface;
    }

}

