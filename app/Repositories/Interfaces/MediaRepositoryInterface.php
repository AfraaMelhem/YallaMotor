<?php

namespace App\Repositories\Interfaces;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface MediaRepositoryInterface extends BaseRepositoryInterface
{
    public function createForModel($model, array $data);

}

