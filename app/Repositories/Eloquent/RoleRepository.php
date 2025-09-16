<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Interfaces\RoleRepositoryInterface;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{

 public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function assignPermissions(int|string $roleId, array $permissionIds): bool
    {
        $role = $this->model->findOrFail($roleId);

        $permissions = Permission::whereIn('id', $permissionIds)
            ->where('guard_name', $role->guard_name)
            ->get();

        if ($permissions->isEmpty()) {
            throw new \Exception("No valid permissions found for guard '{$role->guard_name}'");
        }

        $role->syncPermissions($permissions);

        return true;
    }
}
