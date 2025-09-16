<?php

namespace App\Repositories\Interfaces;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface RoleRepositoryInterface extends BaseRepositoryInterface
{

    public function assignPermissions(int|string $roleId, array $permissionIds): bool;

}

