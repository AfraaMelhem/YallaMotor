<?php

namespace App\Repositories\Interfaces;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface AdminRepositoryInterface extends BaseRepositoryInterface
{
public function findByEmail(string $email): mixed;
}

