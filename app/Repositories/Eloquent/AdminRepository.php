<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\AdminRepositoryInterface;
use App\Models\Admin;

class AdminRepository extends BaseRepository implements AdminRepositoryInterface
{

 public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Admin
    {
        return $this->model->where('email', $email)->first();
    }
}
