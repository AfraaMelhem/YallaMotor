<?php


namespace App\Services;

use App\Repositories\Interfaces\RoleRepositoryInterface;

class RoleService
{
    protected $roleRepositoryInterface;

    public function __construct(RoleRepositoryInterface $roleRepositoryInterface)
    {
        $this->roleRepositoryInterface = $roleRepositoryInterface;
    }

}

